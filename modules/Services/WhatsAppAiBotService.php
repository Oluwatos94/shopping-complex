<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
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

    private const LOCATION_FRESH_MINUTES = 60;

    private const MAX_TOOL_ITERATIONS = 10;

    private const LOCATION_REQUESTED_MARKER = 'LOCATION_REQUESTED:';

    private const SEARCH_RADII_KM = [5.0, 15.0, 30.0];

    public function __construct(
        private WhatsAppApiService $apiService,
        private WhatsAppSessionRepository $sessionRepository,
        private WhatsAppInteractionRepository $interactionRepository,
        private VendorService $vendorService,
        private AnalyticsService $analyticsService,
        private AiChatClient $ai,
        private GeoLocationService $geo,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public function handle(string $from, string $messageType, mixed $messageBody, array $message): void
    {
        $lock = Cache::lock('wa:session:'.$from, 30);

        try {
            $lock->block(10, fn () => $this->process($from, $messageType, $messageBody, $message));
        } catch (LockTimeoutException) {
            Log::warning('WhatsApp session lock timeout; processing without lock', ['from' => $from]);
            $this->process($from, $messageType, $messageBody, $message);
        }
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function process(string $from, string $messageType, mixed $messageBody, array $message): void
    {
        $session = $this->sessionRepository->findOrCreate($from);

        $isFirstTime = $session->wasRecentlyCreated;

        if ($session->last_active_at->diffInMinutes(now()) >= self::SESSION_TTL_MINUTES) {
            $this->sessionRepository->resetSession($session);
            $session = $this->sessionRepository->findOrCreate($from);
        }

        $userText = $this->extractUserText($messageType, $messageBody, $message);

        if ($userText === null) {
            $this->apiService->sendText($from, 'I can only read text messages and locations right now. What product are you looking for?');

            return;
        }

        if ($messageType === 'location') {
            $lat = data_get($message, 'location.latitude');
            $lng = data_get($message, 'location.longitude');
            if ($lat !== null && $lng !== null) {
                $label = $this->geo->reverseGeocode((float) $lat, (float) $lng);

                $locationData = ['lat' => (float) $lat, 'lng' => (float) $lng, 'at' => now()->timestamp];
                if ($label !== null && $label !== '') {
                    $locationData['label'] = $label;
                    $userText .= " (near {$label})";
                }

                $session->data = array_merge((array) ($session->data ?? []), ['location' => $locationData]);
            }
        }

        /** @var array<int, array<string, mixed>> $history */
        $history = (array) data_get($session->data, 'history', []);

        $history[] = ['role' => 'user', 'content' => $userText];

        $this->interactionRepository->log([
            'phone_number' => $from,
            'event_type' => WhatsAppInteractionEventEnum::SEARCH,
            'search_query' => mb_substr($userText, 0, 255),
        ]);

        $reply = $this->runAiWithTools($from, $history, $session, $isFirstTime);

        $history[] = [
            'role' => 'assistant',
            'content' => $reply === '' ? '(Sent the buyer a button to share their location.)' : $reply,
        ];

        while (count($history) > self::MAX_HISTORY_MESSAGES) {
            array_splice($history, 0, 2);
        }

        $session->data = array_merge((array) ($session->data ?? []), ['history' => $history]);
        $this->sessionRepository->save($session);

        if ($reply !== '') {
            $this->apiService->sendText($from, $reply);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $history
     */
    private function runAiWithTools(string $from, array $history, WhatsAppSession $session, bool $isFirstTime = false): string
    {
        $tools = $this->defineTools();

        $messages = array_map(fn (array $msg) => [
            'role' => $msg['role'],
            'content' => (string) $msg['content'],
        ], $history);

        $system = $this->systemPrompt();
        if ($isFirstTime) {
            $system .= "\n\nFIRST CONTACT: This is the buyer's very first message to Jiidaa. In your reply this turn, also warmly invite them — in their own language — to save this WhatsApp number as \"Jiidaa\" so they can easily reach us next time. Keep it to one short, friendly line, and do this only once.";
        }

        $payload = [
            'max_tokens' => 2048,
            'system' => $system,
            'messages' => $messages,
            'tools' => $tools,
        ];

        $response = $this->ai->createMessage($payload);

        $directMessageSent = false;

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
                $toolName = (string) $block['name'];
                $result = $this->executeTool($toolName, $input, $from, $session);

                if (str_starts_with($result, self::LOCATION_REQUESTED_MARKER)) {
                    $directMessageSent = true;
                }

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

        if ($directMessageSent) {
            return '';
        }

        foreach ((array) ($response['content'] ?? []) as $block) {
            if (($block['type'] ?? '') === 'text' && trim((string) $block['text']) !== '') {
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
            'get_vendor_products' => $this->toolGetVendorProducts($input, $session),
            'get_vendor_contact' => $this->toolGetVendorContact($input, $from, $session),
            'request_location' => $this->toolRequestLocation($input, $from, $session),
            default => 'Unknown tool.',
        };
    }

    /**
     * Send the buyer a native WhatsApp "Send location" button.
     *
     * @param  array<string, mixed>  $input
     */
    private function toolRequestLocation(array $input, string $from, WhatsAppSession $session): string
    {
        $stored = $this->storedLocation($session);
        if ($stored !== null && $stored['fresh']) {
            return sprintf(
                'The buyer already shared their location recently (latitude %s, longitude %s). Do not ask again — call search_vendors with these coordinates.',
                $stored['lat'],
                $stored['lng'],
            );
        }

        $message = trim((string) ($input['message'] ?? ''));
        if ($message === '') {
            $message = 'Please tap the button below to share your location so I can find vendors near you.';
        }

        return $this->promptForLocation($from, $message);
    }

    private function promptForLocation(string $from, string $message): string
    {
        $this->apiService->sendLocationRequest($from, $message);

        return self::LOCATION_REQUESTED_MARKER.' A "Send location" button was shown to the buyer. Wait for them to share their location before searching for nearby vendors.';
    }

    /**
     * @return array{lat: float, lng: float, fresh: bool}|null
     */
    private function storedLocation(WhatsAppSession $session): ?array
    {
        $stored = data_get($session->data, 'location');
        if (! is_array($stored) || ! isset($stored['lat'], $stored['lng'])) {
            return null;
        }

        $at = isset($stored['at']) ? (int) $stored['at'] : 0;
        $ageMinutes = $at > 0 ? (now()->timestamp - $at) / 60 : PHP_INT_MAX;

        return [
            'lat' => (float) $stored['lat'],
            'lng' => (float) $stored['lng'],
            'fresh' => $ageMinutes < self::LOCATION_FRESH_MINUTES,
        ];
    }

    /**
     * Mark the saved location as just-confirmed so we stop treating it as stale
     * for the rest of this conversation.
     */
    private function touchStoredLocation(WhatsAppSession $session): void
    {
        $stored = data_get($session->data, 'location');
        if (is_array($stored)) {
            $stored['at'] = now()->timestamp;
            $session->data = array_merge((array) ($session->data ?? []), ['location' => $stored]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolSearchVendors(array $input, string $from, WhatsAppSession $session): string
    {
        $query = (string) ($input['query'] ?? '');
        $lat = isset($input['latitude']) ? (float) $input['latitude'] : null;
        $lng = isset($input['longitude']) ? (float) $input['longitude'] : null;
        $searchEverywhere = (bool) ($input['search_everywhere'] ?? false);
        $useSaved = (bool) ($input['use_saved_location'] ?? false);

        if (($lat === null || $lng === null) && ! $searchEverywhere) {
            $stored = $this->storedLocation($session);

            if ($stored === null) {
                return $this->promptForLocation(
                    $from,
                    'Please tap the button below to share your location so I can find the nearest vendors for you.'
                );
            }

            if (! $stored['fresh'] && ! $useSaved) {
                return 'The buyer shared a location earlier in this chat, but it is now more than '
                    .self::LOCATION_FRESH_MINUTES.' minutes old, so they may have moved. Do NOT search yet. '
                    .'Ask the buyer, in their own language, whether to use that saved location or share their current location now. '
                    .'If they choose the saved one, call search_vendors again with use_saved_location=true. '
                    .'If they want to share a new one, call request_location.';
            }

            $lat = $stored['lat'];
            $lng = $stored['lng'];

            if ($useSaved) {
                $this->touchStoredLocation($session);
            }
        }

        if ($searchEverywhere) {
            $vendors = $this->vendorService->findByQuery($query);

            if ($vendors->isEmpty()) {
                return "No vendor on Jiidaa currently has \"{$query}\" listed. This is specific to THIS search term only — it does NOT mean the platform is empty. Tell the buyer nothing matches this item yet, suggest a related term, and do NOT invent any vendors or claim there are no vendors at all.";
            }

            $this->logVendorViews($vendors, $from, $query, null, null);

            return 'Found '.count($vendors)." vendor(s) across the platform (distance unknown):\n".$this->presentVendors($session, $vendors);
        }

        if ($lat === null || $lng === null) {
            return $this->promptForLocation(
                $from,
                'Please tap the button below to share your location so I can find the nearest vendors for you.'
            );
        }

        foreach (self::SEARCH_RADII_KM as $radius) {
            $vendors = $this->vendorService->findNearbyByQuery($lat, $lng, $query, $radius);

            if ($vendors->isNotEmpty()) {
                $this->logVendorViews($vendors, $from, $query, $lat, $lng);

                return 'Found '.count($vendors)." vendor(s) within {$radius} km (distances shown):\n"
                    .$this->presentVendors($session, $vendors);
            }
        }

        $global = $this->vendorService->findByQuery($query, lat: $lat, lng: $lng);

        if ($global->isEmpty()) {
            return "No vendor on Jiidaa currently has \"{$query}\" listed. This is specific to THIS search term only — it does NOT mean the platform is empty or has no vendors. Tell the buyer nothing matches this item yet, suggest a related term, and do NOT invent any vendors or claim there are no vendors at all.";
        }

        $this->logVendorViews($global, $from, $query, $lat, $lng);

        $widest = (int) max(self::SEARCH_RADII_KM);

        return "No vendors within {$widest} km, but these match \"{$query}\" further out on Jiidaa (distances shown):\n"
            .$this->presentVendors($session, $global)
            ."\nTell the buyer these aren't nearby, and how far each one is.";
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $vendors
     */
    private function presentVendors(WhatsAppSession $session, \Illuminate\Support\Collection $vendors): string
    {
        $vendors = $vendors->values();

        $session->data = array_merge((array) ($session->data ?? []), [
            'last_results' => $vendors->map(fn (User $vendor, int $i) => [
                'pos' => $i + 1,
                'id' => $vendor->id,
                'name' => $vendor->business_name ?? $vendor->name,
            ])->all(),
        ]);

        return $vendors->map(fn (User $vendor, int $i) => sprintf(
            '%d. %s | %d products%s',
            $i + 1,
            $vendor->business_name ?? $vendor->name,
            $vendor->active_products_count ?? 0,
            isset($vendor->distance_km) ? ' | '.number_format((float) $vendor->distance_km, 1).' km away' : '',
        ))->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function resolveVendorId(array $input, WhatsAppSession $session): ?int
    {
        $position = isset($input['position']) ? (int) $input['position'] : 0;
        if ($position < 1) {
            return null;
        }

        /** @var array<int, array<string, mixed>> $results */
        $results = (array) data_get($session->data, 'last_results', []);
        foreach ($results as $row) {
            if ((int) ($row['pos'] ?? 0) === $position) {
                return (int) $row['id'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolGetVendorProducts(array $input, WhatsAppSession $session): string
    {
        $vendorId = $this->resolveVendorId($input, $session);
        if ($vendorId === null) {
            return 'Not sure which vendor the buyer means. Ask them which of the listed vendors they want — by name or by its number in the list.';
        }

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
                '- %s | ₦%s',
                $p->name,
                number_format((float) $p->price, 0),
            );
        })->implode("\n");

        return "Products from *{$vendor->business_name}*:\n".$lines;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function toolGetVendorContact(array $input, string $from, WhatsAppSession $session): string
    {
        $vendorId = $this->resolveVendorId($input, $session);
        if ($vendorId === null) {
            return 'Not sure which vendor the buyer means. Ask them which of the listed vendors they want — by name or by its number in the list.';
        }

        try {
            $vendor = $this->vendorService->getVendorById($vendorId);
        } catch (\Throwable $e) {
            Log::warning('toolGetVendorContact failed', ['vendor_id' => $vendorId, 'error' => $e->getMessage()]);

            return 'Vendor not found.';
        }

        $this->interactionRepository->log([
            'phone_number' => $from,
            'event_type' => WhatsAppInteractionEventEnum::CONTACT_REQUESTED,
            'vendor_id' => $vendorId,
        ]);

        $name = $vendor->business_name ?? $vendor->name;
        $profileUrl = $vendor->slug ? url('/vendors/'.$vendor->slug) : null;

        $digits = empty($vendor->whatsapp_number)
            ? null
            : $this->normalizeWhatsAppNumber((string) $vendor->whatsapp_number);

        if ($digits === null && $profileUrl === null) {
            return "{$name} has not added a WhatsApp number or a public profile yet. Tell the buyer this in their own language.";
        }

        $lines = ["*{$name}*"];
        if ($digits !== null) {
            $lines[] = "WhatsApp: https://wa.me/{$digits}";
        }
        if ($profileUrl !== null) {
            $lines[] = "Profile: {$profileUrl}";
        }

        $this->apiService->sendText($from, implode("\n", $lines));

        if ($digits !== null && $profileUrl !== null) {
            $shared = 'their WhatsApp link and profile link';
        } elseif ($digits !== null) {
            $shared = 'their WhatsApp link';
        } else {
            $shared = 'their profile link (no usable WhatsApp number on file)';
        }

        return "Already sent {$name}'s contact card ({$shared}) to the buyer in a separate message. "
            ."In your reply, just confirm in the buyer's own language that you've shared {$name}'s details — "
            .'do NOT type out any link or phone number yourself; the buyer already has the exact one.';
    }

    private function normalizeWhatsAppNumber(string $number): ?string
    {
        $digits = (string) preg_replace('/[^0-9]/', '', $number);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        $national = match (true) {
            str_starts_with($digits, '2340') && strlen($digits) === 14 => substr($digits, 4),
            str_starts_with($digits, '234') && strlen($digits) === 13 => substr($digits, 3),
            str_starts_with($digits, '0') && strlen($digits) === 11 => substr($digits, 1),
            strlen($digits) === 10 => $digits,
            default => null,
        };

        if ($national === null || preg_match('/^[789]\d{9}$/', $national) !== 1) {
            return null;
        }

        return '234'.$national;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defineTools(): array
    {
        return [
            [
                'name' => 'search_vendors',
                'description' => "Search for vendors. The query matches a vendor's business NAME, their product names/descriptions, their product TAGS, and their category — so the buyer can search by what they sell (e.g. \"furniture\", \"sneakers\"), by a product tag, OR by a specific vendor's name (e.g. \"Royal Priesthood Furniture\"). Pass the buyer's coordinates and the tool automatically widens the search radius step by step before reporting distance, so you do NOT manage radius yourself. Keep the query to the core thing the buyer wants (e.g. \"laundry\", not \"laundry service near me\"). If even the widest search finds nothing, set search_everywhere=true to search the whole platform regardless of distance.",
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'The core product, service, tag, or vendor name the buyer wants — just the keyword(s), e.g. "laundry", "shoes", "jollof rice". Drop filler like "service", "near me", "where can I buy".'],
                        'latitude' => ['type' => 'number', 'description' => 'Buyer latitude (optional)'],
                        'longitude' => ['type' => 'number', 'description' => 'Buyer longitude (optional)'],
                        'search_everywhere' => ['type' => 'boolean', 'description' => 'When true, ignore distance and search ALL vendors on the platform. Use only after a normal (located) search returns nothing.'],
                        'use_saved_location' => ['type' => 'boolean', 'description' => 'Set true ONLY when the buyer has confirmed they want to reuse a previously saved location that the tool flagged as possibly outdated.'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'get_vendor_products',
                'description' => 'Get the product list for a vendor from the most recent search results. Identify the vendor by their POSITION in that list (1 for the first vendor shown, 2 for the second, etc.) — this is how the buyer refers to them ("the first one", "number 2", "1 to 3"). For a range, call this once per position.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'position' => ['type' => 'integer', 'description' => '1-based position of the vendor in the most recent search results (1 = first listed).'],
                    ],
                    'required' => ['position'],
                ],
            ],
            [
                'name' => 'get_vendor_contact',
                'description' => "Get a vendor's WhatsApp contact link and Jiidaa profile link so the buyer can reach them. Identify the vendor by their POSITION in the most recent search results (1 = first listed, 2 = second, etc.) — this is how the buyer refers to them. Returns only the requested vendor's real details.",
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'position' => ['type' => 'integer', 'description' => '1-based position of the vendor in the most recent search results (1 = first listed).'],
                    ],
                    'required' => ['position'],
                ],
            ],
            [
                'name' => 'request_location',
                'description' => "Ask the buyer to share their GPS location by showing them a native WhatsApp 'Send location' button. Use this whenever you need to find nearby vendors or measure distance and you don't already have the buyer's coordinates. Never ask the buyer to type coordinates manually — use this tool instead.",
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => ['type' => 'string', 'description' => "Short prompt to show above the button, written in the buyer's own language (e.g. ask them to tap the button to share their location)."],
                    ],
                    'required' => ['message'],
                ],
            ],
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are a helpful shopping assistant for Jiidaa, a Nigerian local vendor discovery platform. You help buyers find vendors and products near them.

You help buyers:
- Find vendors selling specific products or services
- Browse a vendor's product catalogue
- Get a vendor's WhatsApp contact to negotiate directly

LANGUAGE:
- Reply in the SAME language the buyer writes in, and keep it consistent for the whole conversation. The five common languages are English, Nigerian Pidgin, Yoruba, Hausa and Igbo. Identify the language carefully — do NOT default to Yoruba:
  - English → reply in plain English.
  - Nigerian Pidgin is ENGLISH-BASED. Tell-tale words: "wey", "dey", "wan", "abeg", "make", "no wahala", "una", "dem", "comot", "wetin", "sabi", "na". Example: "I need vendor wey dey sell food" → reply in Nigerian Pidgin, NOT Yoruba.
  - Yoruba tell-tale words: "Mo fẹ́"/"Mo fe", "ọjà", "olùtajà", "oúnjẹ", "báwo", "jọ̀wọ́", "ẹ jọ̀wọ́", "kí". Example: "Mo fe bata" → reply in Yoruba.
  - Hausa tell-tale words: "ina", "neman", "ina son", "kaya", "nawa", "kana", "ina nemi", "abinci", "don Allah". Example: "Ina neman mai sayar da abinci" → reply in Hausa.
  - Igbo tell-tale words: "achọrọ m", "ebee", "onye", "na-ere", "kedu", "biko", "nke", "m". Example: "Achọrọ m onye na-ere nri" → reply in Igbo.
- Judge by the sentence's overall language, not a single word — buyers often mix an English product word into a local sentence (e.g. "Mo fe sneakers" is still Yoruba; "I need sneakers wey dey near me" is still Pidgin; "Ina son sneakers" is still Hausa). When genuinely unsure, use simple English.

CORE FLOW — ALWAYS LOCATION FIRST:
Jiidaa's whole purpose is to connect a buyer to the NEAREST vendor that sells what they want. So you MUST have the buyer's GPS location before searching.
1. Treat ANY message that names a product or service, or asks where to buy or who sells something, as a product request — e.g. "I need food", "Mo fe bata", "where can I buy a phone", "who sells shoes", "abeg find me tailor", "Ina neman takalmi", "Achọrọ m nri".
2. For a product request, if you do NOT already have the buyer's location in this conversation, you MUST call the request_location tool. It pops up a native WhatsApp "Send location" button. Do NOT call search_vendors yet, and never ask the buyer to type latitude/longitude.
   CRITICAL: Writing "share your location" or "tap the button" as plain text does NOTHING — no button appears and the buyer is stuck. The ONLY way to show the button is to actually CALL the request_location tool. Always call the tool; never just talk about it.
3. Once the buyer shares their location, call search_vendors with the product (translated to English) AND their coordinates, so results are the nearest matching vendors. The shared location may include the area name in parentheses (e.g. "near Ikeja, Lagos") — briefly acknowledge that area in the buyer's own language when you reply (e.g. "Got it, you're around Ikeja, Lagos") so they know you located them correctly. Never invent an area name; only use the one provided.
4. If you ALREADY received the buyer's location earlier in this same conversation and it is still recent, reuse it — do not ask again; go straight to search_vendors with those coordinates.
5. Locations can go stale. If the saved location is too old, search_vendors will NOT search; instead it tells you the location may be outdated. When that happens, ask the buyer (in their language) whether to use the saved location or share their current one. If they want the saved one, call search_vendors again with use_saved_location=true. If they want to share a fresh one, call request_location to pop the "Send location" button. Never assume — let the buyer decide.

SEARCHING:
- search_vendors matches a vendor's business NAME, their product names/descriptions, product TAGS, and category. So you can find vendors by what they sell OR by a specific vendor's name — if a buyer asks "do you have <vendor name>?" or "search by name", use search_vendors with that name. You CAN search by name; never tell the buyer you can't.
- Translate the product or service into English for the search query, even when the buyer writes in another language. Examples — Yoruba: "bata"/"bàtà" → "shoes", "aṣọ" → "clothes", "ata" → "pepper", "ewa" → "beans"; Hausa: "takalmi" → "shoes", "abinci" → "food", "waya" → "phone", "kaya" → "goods"; Igbo: "akpụkpọ ụkwụ" → "shoes", "nri" → "food", "ekwentị" → "phone", "akwa" → "clothes".
- Search for the CORE keyword only — strip filler words like "service", "services", "near me", "where can I buy", "I'm looking for". E.g. "a laundry service near me" → search "laundry"; "where can I buy good shoes" → search "shoes". Generic words like "service" match unrelated vendors, so leave them out.
- If the first search returns nothing, automatically retry with broader or synonymous English terms (e.g. shoes → footwear → sneakers → sandals) BEFORE telling the buyer nothing was found.
- search_vendors automatically widens the distance step by step and, if still nothing, returns the closest matches from further away (with distances). PRESENT exactly what the tool returns and DESCRIBE the results by what they matched — e.g. "here are vendors that came up for 'laundry'". Do NOT assert they are a verified category or service (don't say "I found 3 laundry services" unless the tool result shows that's what they are); the buyer can judge from the names.
- An empty result is ALWAYS specific to that one search term — it NEVER means "no vendors exist on Jiidaa". NEVER tell the buyer the platform has no vendors, and NEVER retract or contradict vendors you already listed earlier in this chat. If a search comes back empty, suggest a related term or try again — do not declare the platform empty.

REFERRING TO RESULTS:
- Search results are NUMBERED (1, 2, 3, …) in the order shown. When the buyer points to a vendor by position ("the first one", "number 2", "give me 1 to 3") or by name, call get_vendor_products / get_vendor_contact with the `position` of that vendor in the most recent results. For a range like "1 to 3", call the tool once per position (1, then 2, then 3).
- You may show the buyer the numbers so they can refer back easily. There are no internal IDs for you to handle — you identify vendors only by their position in the latest results.

CONTACT & ACCURACY (very important):
- To share a vendor's contact, call get_vendor_contact for that exact vendor (by its position in the results). The tool sends the buyer the WhatsApp link and profile link DIRECTLY in a separate message — you will NOT receive the link or number, and you must NEVER type out, guess, invent, or reformat any link or phone number yourself. Doing so risks sending a corrupted, dead link.
- After calling get_vendor_contact, simply confirm to the buyer, in their own language, that you've shared the vendor's contact details (e.g. "I've sent you The Elite Laundry's contact 👍"). The buyer already has the exact, correct details from the tool's message.
- If the tool says the vendor has no WhatsApp number or profile, relay that plainly in the buyer's language. NEVER substitute another vendor's contact.

GENERAL:
- Be friendly and concise — this is WhatsApp, so keep replies short.
- After showing vendors, offer to send a vendor's contact (WhatsApp + profile link) AND show their products — phrase it as one offer, e.g. "Want The Elite Laundry's contact details and products?". Do NOT force an either/or choice between products and contact.
- If the buyer answers an offer with "yes", "both", or anything ambiguous, give BOTH the contact and the products — do not re-ask which one they meant.
- All prices are in Nigerian Naira (₦).
- Never make up vendor names, prices, products, numbers, or links — only use what the tools return.
- If someone asks something unrelated to shopping, politely redirect them.
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
