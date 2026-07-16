<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Events\SupportMessageSentEvent;
use ModulesShoppingComplex\Models\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\SupportMessage;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\SubscriptionRepository;
use ModulesShoppingComplex\Repositories\SupportConversationRepository;
use ModulesShoppingComplex\Repositories\SupportMessageRepository;
use ModulesShoppingComplex\Services\Contracts\AiChatClient;

final readonly class SupportBotService
{
    private const MAX_HISTORY_MESSAGES = 20;

    private const MAX_TOOL_ITERATIONS = 10;

    private const SEARCH_RADII_KM = [5.0, 15.0, 30.0];

    private const LOOSE_MATCH_NOTE = "\nIMPORTANT: These are CLOSE matches only — no vendor matched the exact product or tag, so these matched via their category or a product description and may NOT offer exactly what the buyer asked for. Tell the buyer these are the closest related vendors and that they should confirm with the vendor directly.";

    private const FALLBACK_REPLY = "Sorry, I'm having trouble replying right now. Please try again in a moment, or ask to speak with a human agent and I'll connect you.";

    private const NOT_SIGNED_IN = 'The user is not signed in, so account-specific data cannot be looked up. Ask them to log in to their Jiidaa account first.';

    public function __construct(
        private AiChatClient $ai,
        private SupportConversationRepository $conversationRepository,
        private SupportMessageRepository $messageRepository,
        private VendorService $vendorService,
        private ProductService $productService,
        private SubscriptionService $subscriptionService,
        private SubscriptionRepository $subscriptionRepository,
        private SupportEscalationService $escalationService,
    ) {}

    /**
     * Persist the user's message, generate an AI reply from the thread
     * history (running tools as needed), and persist + return the
     * assistant message.
     */
    public function reply(SupportConversation $conversation, string $userText, ?float $lat = null, ?float $lng = null): SupportMessage
    {

        $escalated = $conversation->status === SupportConversationStatusEnum::AWAITING_AGENT;

        $userMessage = $this->messageRepository->create([
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessageRoleEnum::USER,
            'sender_id' => $conversation->user_id,
            'content' => $userText,
        ]);

        if ($escalated) {
            event(new SupportMessageSentEvent($userMessage));
        }

        $assistantMessage = $this->messageRepository->create([
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessageRoleEnum::ASSISTANT,
            'sender_id' => null,
            'content' => $this->generateReply($conversation, $lat, $lng),
        ]);

        if ($escalated) {
            event(new SupportMessageSentEvent($assistantMessage));
        }

        $this->conversationRepository->updateLastMessageAt($conversation->id);

        return $assistantMessage;
    }

    private function generateReply(SupportConversation $conversation, ?float $lat, ?float $lng): string
    {
        try {
            $messages = $this->buildHistory($conversation);

            $payload = [
                'max_tokens' => 1024,
                'system' => $this->systemPrompt(),
                'messages' => $messages,
                'tools' => $this->defineTools(),
            ];

            $response = $this->ai->createMessage($payload);

            $iterations = 0;
            $humanRequested = false;
            while (($response['stop_reason'] ?? '') === 'tool_use') {
                if (++$iterations > self::MAX_TOOL_ITERATIONS) {
                    Log::warning('Support bot tool loop exceeded max iterations', [
                        'conversation_id' => $conversation->id,
                    ]);
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

                    if ((string) $block['name'] === 'request_human') {
                        $humanRequested = true;
                    }

                    $toolResults[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $block['id'],
                        'content' => $this->executeTool((string) $block['name'], $input, $conversation, $lat, $lng),
                    ];
                }

                $messages[] = ['role' => 'user', 'content' => $toolResults];

                $payload['messages'] = $messages;
                $response = $this->ai->createMessage($payload);
            }

            if ($humanRequested) {
                $this->escalationService->escalate($conversation);
            }

            foreach ((array) ($response['content'] ?? []) as $block) {
                if (($block['type'] ?? '') === 'text' && trim((string) $block['text']) !== '') {
                    return (string) $block['text'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Support bot reply failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        return self::FALLBACK_REPLY;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function executeTool(string $name, array $input, SupportConversation $conversation, ?float $lat, ?float $lng): string
    {
        return match ($name) {
            'search_products' => $this->toolSearchProducts($input),
            'search_vendors' => $this->toolSearchVendors($input, $lat, $lng),
            'get_payment_status' => $this->toolGetPaymentStatus($input, $conversation),
            'get_my_subscription' => $this->toolGetMySubscription($conversation),
            'request_human' => 'Human support agents have been notified and one will join this conversation shortly. Tell the user a human agent will be with them soon, and keep helping them in the meantime if they ask further questions.',
            default => 'Unknown tool.',
        };
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolSearchProducts(array $input): string
    {
        $query = trim((string) ($input['query'] ?? ''));
        if ($query === '') {
            return 'No search query given.';
        }

        $products = $this->productService->searchProducts($query, 5)->items();

        if ($products === []) {
            return "No product on Jiidaa currently matches \"{$query}\". This only means nothing matches this term — suggest a related term; do NOT invent products.";
        }

        $lines = array_map(function (Product $product) {
            $vendorName = $product->vendor->business_name ?? $product->vendor->name;

            return sprintf('- %s | ₦%s | sold by %s', $product->name, number_format((float) $product->price, 0), $vendorName);
        }, $products);

        return "Products matching \"{$query}\":\n".implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolSearchVendors(array $input, ?float $lat, ?float $lng): string
    {
        $query = trim((string) ($input['query'] ?? ''));
        if ($query === '') {
            return 'No search query given.';
        }

        if ($lat === null || $lng === null) {
            foreach ([false, true] as $loose) {
                $vendors = $this->vendorService->findByQuery($query, loose: $loose);

                if ($vendors->isNotEmpty()) {
                    return 'The user has not shared their device location, so these matches are NOT sorted by distance. Found '.count($vendors)." vendor(s) matching \"{$query}\":\n"
                        .$this->presentVendors($vendors)
                        .($loose ? self::LOOSE_MATCH_NOTE : '')
                        ."\nInvite the user to tap the location pin button next to the message box if they want the nearest vendors to them.";
                }
            }

            return $this->noVendorMatch($query);
        }

        foreach ([false, true] as $loose) {
            foreach (self::SEARCH_RADII_KM as $radius) {
                $vendors = $this->vendorService->findNearbyByQuery($lat, $lng, $query, $radius, $loose);

                if ($vendors->isNotEmpty()) {
                    return 'Found '.count($vendors)." vendor(s) within {$radius} km:\n"
                        .$this->presentVendors($vendors)
                        .($loose ? self::LOOSE_MATCH_NOTE : '');
                }
            }
        }

        foreach ([false, true] as $loose) {
            $global = $this->vendorService->findByQuery($query, lat: $lat, lng: $lng, loose: $loose);

            if ($global->isNotEmpty()) {
                return 'No vendors near the user, but found '.count($global)." elsewhere on the platform (distances shown):\n"
                    .$this->presentVendors($global)
                    .($loose ? self::LOOSE_MATCH_NOTE : '');
            }
        }

        return $this->noVendorMatch($query);
    }

    private function noVendorMatch(string $query): string
    {
        return "No vendor on Jiidaa currently matches \"{$query}\". This only means nothing matches this term — suggest a related term; do NOT invent vendors.";
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $vendors
     */
    private function presentVendors($vendors): string
    {
        return $vendors->map(function (User $vendor) {
            $distance = isset($vendor->distance_km) ? sprintf(' | %.1f km away', (float) $vendor->distance_km) : '';

            return sprintf(
                '- %s%s%s',
                $vendor->business_name ?? $vendor->name,
                $distance,
                $vendor->slug ? ' | '.url('/vendors/'.$vendor->slug) : '',
            );
        })->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolGetPaymentStatus(array $input, SupportConversation $conversation): string
    {
        if ($conversation->user_id === null) {
            return self::NOT_SIGNED_IN;
        }

        $reference = trim((string) ($input['reference'] ?? ''));
        if ($reference === '') {
            return 'No payment reference given. Ask the user for the payment reference.';
        }

        $subscription = $this->subscriptionRepository->findByPaymentReference($reference);

        if ($subscription === null || $subscription->vendor_id !== $conversation->user_id) {
            return "No payment with reference \"{$reference}\" was found on this user's account.";
        }

        return sprintf(
            'Payment %s: %s plan, ₦%s, subscription status %s, paid via %s, started %s, expires %s.',
            $reference,
            $subscription->plan->name,
            number_format((float) $subscription->amount_paid, 0),
            $subscription->status->value,
            $subscription->payment_method->value,
            $subscription->started_at->toFormattedDateString(),
            $subscription->expires_at->toFormattedDateString(),
        );
    }

    private function toolGetMySubscription(SupportConversation $conversation): string
    {
        if ($conversation->user_id === null) {
            return self::NOT_SIGNED_IN;
        }

        if ($conversation->user?->role !== 'vendor') {
            return 'This user is not a vendor — only vendor accounts have subscriptions.';
        }

        $subscription = $this->subscriptionService->getVendorSubscription($conversation->user_id);

        if ($subscription === null) {
            return 'This vendor has no active subscription. They can choose a plan from their vendor dashboard to stay discoverable.';
        }

        return sprintf(
            'Active subscription: %s plan, ₦%s, expires %s, paid via %s.',
            $subscription->plan->name,
            number_format((float) $subscription->amount_paid, 0),
            $subscription->expires_at->toFormattedDateString(),
            $subscription->payment_method->value,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defineTools(): array
    {
        return [
            [
                'name' => 'search_products',
                'description' => 'Search products listed on Jiidaa by name, description or tag. Use this to answer "do you sell X?" or price questions with real data. Keep the query to the core keyword(s).',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'The core product keyword(s), e.g. "sneakers".'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'search_vendors',
                'description' => "Search vendors on Jiidaa by business name, what they sell, or category. Automatically uses the buyer's shared device location to return the nearest vendors first, with distance and profile link.",
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'The vendor name, product or category to search for.'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'get_payment_status',
                'description' => "Look up one of the signed-in user's own subscription payments by its payment reference. Only ever returns this user's data.",
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'reference' => ['type' => 'string', 'description' => 'The payment reference the user was given at checkout.'],
                    ],
                    'required' => ['reference'],
                ],
            ],
            [
                'name' => 'get_my_subscription',
                'description' => "Get the signed-in vendor's current subscription (plan, amount, expiry). Only ever returns this user's data.",
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                    'required' => [],
                ],
            ],
            [
                'name' => 'request_human',
                'description' => 'Hand this conversation off to a human support agent. Use when the user asks for a human, or when you cannot resolve their issue after trying.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildHistory(SupportConversation $conversation): array
    {
        return $this->messageRepository
            ->getRecentForConversation($conversation->id, self::MAX_HISTORY_MESSAGES)
            ->map(fn (SupportMessage $message) => [
                'role' => $message->role === SupportMessageRoleEnum::USER ? 'user' : 'assistant',
                'content' => $message->content,
            ])
            ->all();
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are the customer support assistant for Jiidaa, a Nigerian GPS-powered, WhatsApp-native marketplace that connects buyers to trusted local vendors near them.

WHAT YOU HELP WITH:
- Finding vendors or products: buyers browse the website or message the Jiidaa WhatsApp bot, share their location, and get the nearest matching vendors. Buying, negotiation and delivery happen directly between buyer and vendor on WhatsApp — Jiidaa does not process orders, buyer payments, or delivery.
- Becoming a vendor: click "Become a vendor" on the website to register your profile, then start adding your products and business location. Subscribe to a plan to enjoy more features.
- Vendor subscriptions: plans and payment are managed from the vendor dashboard.
- Account issues and general questions about how Jiidaa works.

TOOLS:
- Use search_products / search_vendors to answer product or vendor questions with live platform data instead of guessing.
- Vendor search works without location (by business name or category), but the buyer's device location lets you recommend the NEAREST vendors. If search_vendors reports that no location was shared, share any matches it returned and invite the buyer to tap the location pin button next to the message box to get vendors near them.
- When the user asks about a subscription payment and gives its payment reference, use get_payment_status. For their current plan, use get_my_subscription. These tools are already scoped to the signed-in user — never ask for or accept another person's account details.
- If a lookup returns nothing, say so plainly and suggest what to try next — never present invented data as a result.

TONE & LANGUAGE:
- Be friendly, clear and concise.
- Reply in the SAME language the user writes in (English, Nigerian Pidgin, Yoruba, Hausa or Igbo) and keep it consistent. When unsure, use simple English.
- All prices are in Nigerian Naira (₦).
- You are replying in a small chat window that renders PLAIN TEXT only: never use markdown (no **, *, #, or [text](url)). Write vendor profile links as full bare URLs on their own line.

ACCURACY:
- Never invent vendors, products, prices, order details or account information. Only state what the tools return or what you know about how Jiidaa works.

HUMAN HANDOFF:
- If you cannot resolve the issue, if the user is frustrated, or if they ask for a person, offer to connect them to a human support agent and ask them to confirm they want that.
- Once they confirm (or clearly ask for a human), call the request_human tool — do not just say an agent is coming without calling it.
PROMPT;
    }
}
