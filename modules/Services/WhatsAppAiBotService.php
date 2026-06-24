<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Models\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Models\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\WhatsAppInteraction;
use ModulesShoppingComplex\Models\WhatsAppSession;
use ModulesShoppingComplex\Repositories\WhatsAppInteractionRepository;
use ModulesShoppingComplex\Repositories\WhatsAppSessionRepository;
use ModulesShoppingComplex\Services\Contracts\AiChatClient;

final readonly class WhatsAppAiBotService
{
    private const MAX_HISTORY_MESSAGES = 20;

    private const SESSION_TTL_MINUTES = 60;

    private const MAX_TOOL_ITERATIONS = 10;

    public function __construct(
        private WhatsAppApiService $apiService,
        private WhatsAppSessionRepository $sessionRepository,
        private WhatsAppInteractionRepository $interactionRepository,
        private VendorService $vendorService,
        private AnalyticsService $analyticsService,
        private AiChatClient $ai,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public function handle(string $from, string $messageType, mixed $messageBody, array $message): void
    {
        $session = $this->sessionRepository->findOrCreate($from);

        if ($session->last_active_at->diffInMinutes(now()) >= self::SESSION_TTL_MINUTES) {
            $this->sessionRepository->resetSession($session);
            $session = $this->sessionRepository->findOrCreate($from);
        }

        $userText = $this->extractUserText($messageType, $messageBody, $message);

        if ($userText === null) {
            $this->apiService->sendText($from, 'I can only read text messages and locations right now. What product are you looking for?');

            return;
        }

        /** @var array<int, array<string, mixed>> $history */
        $history = (array) data_get($session->data, 'history', []);

        $history[] = ['role' => 'user', 'content' => $userText];

        $this->interactionRepository->log([
            'phone_number' => $from,
            'event_type' => WhatsAppInteractionEventEnum::SEARCH,
            'search_query' => mb_substr($userText, 0, 255),
        ]);

        $reply = $this->runAiWithTools($from, $history, $session);

        $history[] = ['role' => 'assistant', 'content' => $reply];

        while (count($history) > self::MAX_HISTORY_MESSAGES) {
            array_splice($history, 0, 2);
        }

        $session->data = array_merge((array) ($session->data ?? []), ['history' => $history]);
        $this->sessionRepository->save($session);

        $this->apiService->sendText($from, $reply);
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     */
    private function runAiWithTools(string $from, array $history, WhatsAppSession $session): string
    {
        $tools = $this->defineTools();

        $messages = array_map(fn (array $msg) => [
            'role' => $msg['role'],
            'content' => (string) $msg['content'],
        ], $history);

        $payload = [
            'max_tokens' => 2048,
            'system' => $this->systemPrompt(),
            'messages' => $messages,
            'tools' => $tools,
        ];

        $response = $this->ai->createMessage($payload);

        $iterations = 0;
        while (($response['stop_reason'] ?? '') === 'tool_use') {
            if (++$iterations > self::MAX_TOOL_ITERATIONS) {
                Log::warning('AI tool loop exceeded max iterations', ['from' => $from]);
                break;
            }

            $content = (array) ($response['content'] ?? []);

            $messages[] = ['role' => 'assistant', 'content' => $content];

            $toolResults = [];
            foreach ($content as $block) {
                if (($block['type'] ?? '') !== 'tool_use') {
                    continue;
                }
                /** @var array<string, mixed> $input */
                $input = (array) ($block['input'] ?? []);
                $result = $this->executeTool((string) $block['name'], $input, $from, $session);
                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content' => $result,
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $toolResults];

            $payload['messages'] = $messages;
            $response = $this->ai->createMessage($payload);
        }

        foreach ((array) ($response['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text') {
                return (string) $block['text'];
            }
        }

        return "Sorry, I couldn't process your request. Please try again.";
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function executeTool(string $name, array $input, string $from, WhatsAppSession $session): string
    {
        return match ($name) {
            'search_vendors' => $this->toolSearchVendors($input, $from, $session),
            'get_vendor_products' => $this->toolGetVendorProducts($input),
            'get_vendor_contact' => $this->toolGetVendorContact($input, $from),
            default => 'Unknown tool.',
        };
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolSearchVendors(array $input, string $from, WhatsAppSession $session): string
    {
        $query = (string) ($input['query'] ?? '');
        $lat = isset($input['latitude']) ? (float) $input['latitude'] : null;
        $lng = isset($input['longitude']) ? (float) $input['longitude'] : null;
        $radius = isset($input['radius_km']) ? (float) $input['radius_km'] : 5.0;

        if ($lat !== null && $lng !== null) {
            $vendors = $this->vendorService->findNearbyByQuery($lat, $lng, $query, $radius);
        } else {
            $vendors = $this->vendorService->findByQuery($query);
        }

        if ($vendors->isEmpty()) {
            return "No vendors found for \"{$query}\"".($lat !== null ? " within {$radius} km" : '').'.';
        }

        $this->logVendorViews($vendors, $from, $query, $lat, $lng);

        $lines = $vendors->map(fn (User $vendor) => sprintf(
            '- ID:%d | %s | %d products%s',
            $vendor->id,
            $vendor->business_name ?? $vendor->name,
            $vendor->active_products_count ?? 0,
            isset($vendor->distance_km) ? ' | '.number_format((float) $vendor->distance_km, 1).' km away' : ''
        ))->implode("\n");

        return 'Found '.count($vendors)." vendor(s):\n".$lines;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolGetVendorProducts(array $input): string
    {
        $vendorId = (int) ($input['vendor_id'] ?? 0);

        try {
            $vendor = $this->vendorService->getVendorById($vendorId);
        } catch (\Throwable $e) {
            Log::warning('toolGetVendorProducts failed', ['vendor_id' => $vendorId, 'error' => $e->getMessage()]);

            return 'Vendor not found.';
        }

        $products = $vendor->products()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(10)
            ->get();

        if ($products->isEmpty()) {
            return 'This vendor has no active products listed.';
        }

        $lines = $products->map(function (\Illuminate\Database\Eloquent\Model $p) {
            /** @var \ModulesShoppingComplex\Models\Product $p */
            return sprintf(
                '- %s | ₦%s | %s',
                $p->name,
                number_format((float) $p->price, 0),
                ((int) $p->stock) > 0 ? 'In stock' : 'Out of stock'
            );
        })->implode("\n");

        return "Products from *{$vendor->business_name}*:\n".$lines;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolGetVendorContact(array $input, string $from): string
    {
        $vendorId = (int) ($input['vendor_id'] ?? 0);

        try {
            $vendor = $this->vendorService->getVendorById($vendorId);
        } catch (\Throwable $e) {
            Log::warning('toolGetVendorContact failed', ['vendor_id' => $vendorId, 'error' => $e->getMessage()]);

            return 'Vendor not found.';
        }

        if (empty($vendor->whatsapp_number)) {
            return 'This vendor has not set up a WhatsApp contact yet.';
        }

        $this->interactionRepository->log([
            'phone_number' => $from,
            'event_type' => WhatsAppInteractionEventEnum::CONTACT_REQUESTED,
            'vendor_id' => $vendorId,
        ]);

        $digits = (string) preg_replace('/[^0-9]/', '', (string) $vendor->whatsapp_number);
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '234'.substr($digits, 1);
        }

        return "WhatsApp link for *{$vendor->business_name}*: https://wa.me/{$digits}";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defineTools(): array
    {
        return [
            [
                'name' => 'search_vendors',
                'description' => 'Search for vendors selling a product. Use this whenever the buyer mentions a product or service they want. If they share their location coordinates, pass them to find nearby vendors. Otherwise search all vendors.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Product or service the buyer is looking for'],
                        'latitude' => ['type' => 'number', 'description' => 'Buyer latitude (optional)'],
                        'longitude' => ['type' => 'number', 'description' => 'Buyer longitude (optional)'],
                        'radius_km' => ['type' => 'number', 'description' => 'Search radius in km (default 5, max 100)'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'get_vendor_products',
                'description' => 'Get the product list for a specific vendor by their ID.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'vendor_id' => ['type' => 'integer', 'description' => 'The vendor ID from search results'],
                    ],
                    'required' => ['vendor_id'],
                ],
            ],
            [
                'name' => 'get_vendor_contact',
                'description' => "Get a vendor's WhatsApp contact link so the buyer can chat with them directly.",
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'vendor_id' => ['type' => 'integer', 'description' => 'The vendor ID'],
                    ],
                    'required' => ['vendor_id'],
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a helpful shopping assistant for jiidaa, a Nigerian local vendor discovery platform. Your job is to help buyers find vendors and products near them.

You help buyers:
- Find vendors selling specific products or services
- Browse a vendor's product catalogue
- Get a vendor's WhatsApp contact to negotiate directly

Guidelines:
- Be friendly, concise, and conversational. Keep replies short — this is WhatsApp.
- If a buyer mentions a product, immediately use the search_vendors tool to find relevant vendors.
- If a buyer shares their location (latitude/longitude), use it to find nearby vendors.
- If no nearby vendors are found within the default radius, automatically try a wider range (10km, then 20km, then search all).
- After showing vendors, offer to show their products or get their contact.
- All prices are in Nigerian Naira (₦).
- If someone asks something unrelated to shopping, politely redirect them.
- Never make up vendor names, prices, or products — only use what the tools return.
PROMPT;
    }

    private function extractUserText(string $messageType, mixed $messageBody, array $rawMessage): ?string
    {
        if ($messageType === 'text' && is_string($messageBody)) {
            return $messageBody;
        }

        if ($messageType === 'location') {
            $lat = $rawMessage['location']['latitude'] ?? null;
            $lng = $rawMessage['location']['longitude'] ?? null;
            if ($lat !== null && $lng !== null) {
                return "My location: latitude {$lat}, longitude {$lng}";
            }
        }

        if ($messageType === 'interactive') {
            return is_string($messageBody) ? $messageBody : null;
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $vendors
     */
    private function logVendorViews(\Illuminate\Support\Collection $vendors, string $from, string $query, ?float $lat, ?float $lng): void
    {
        $now = now()->toDateTimeString();

        WhatsAppInteraction::insert($vendors->map(fn (User $vendor) => [
            'phone_number' => $from,
            'event_type' => WhatsAppInteractionEventEnum::VENDOR_VIEWED->value,
            'search_query' => $query,
            'vendor_id' => $vendor->id,
            'buyer_latitude' => $lat,
            'buyer_longitude' => $lng,
            'created_at' => $now,
        ])->all());

        foreach ($vendors as $vendor) {
            $this->analyticsService->recordProfileView(
                vendorId: $vendor->id,
                viewerId: null,
                ipAddress: null,
                source: ViewSourceEnum::WHATSAPP,
            );
        }
    }
}
