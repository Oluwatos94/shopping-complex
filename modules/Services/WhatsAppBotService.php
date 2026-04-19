<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Models\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Models\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\Models\Enums\WhatsAppSessionStateEnum;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\WhatsAppInteraction;
use ModulesShoppingComplex\Models\WhatsAppSession;
use ModulesShoppingComplex\Repositories\WhatsAppInteractionRepository;
use ModulesShoppingComplex\Repositories\WhatsAppSessionRepository;

final readonly class WhatsAppBotService
{
    private const SESSION_TTL_MINUTES = 30;

    private const PRODUCTS_PER_PAGE = 10;

    private const SEARCH_QUERY_MAX_LENGTH = 100;

    private const SEARCH_QUERY_MIN_LENGTH = 2;

    public function __construct(
        private WhatsAppApiService $apiService,
        private WhatsAppSessionRepository $sessionRepository,
        private WhatsAppInteractionRepository $interactionRepository,
        private VendorService $vendorService,
        private AnalyticsService $analyticsService,
    ) {}

    /**
     * Entry point called by ProcessWhatsAppWebhook.
     *
     * @param  array<string, mixed>  $message  Single message object from the Meta payload
     */
    public function handle(string $from, string $messageType, mixed $messageBody, array $message): void
    {
        // Requires a cache driver with atomic lock support (Redis, Memcached).
        $lock = Cache::lock("whatsapp_session:{$from}", 10);
        $lock->block(5);

        try {
            $session = $this->sessionRepository->findOrCreate($from);

            // Expire stale sessions
            if ($session->last_active_at->diffInMinutes(now()) >= self::SESSION_TTL_MINUTES) {
                $this->sessionRepository->resetSession($session);
            }

            // Global commands — work from any state
            $text = is_string($messageBody) ? strtoupper(trim($messageBody)) : '';

            if (in_array($text, ['MENU', 'HI', 'HELLO', 'START'], true)) {
                $this->sessionRepository->resetSession($session);
                $this->apiService->sendText($from, $this->welcomeMessage());

                return;
            }

            if ($text === 'HELP') {
                $this->apiService->sendText($from, $this->helpMessage());

                return;
            }

            match ($session->state) {
                WhatsAppSessionStateEnum::IDLE => $this->handleIdle($session, $messageType, $messageBody),
                WhatsAppSessionStateEnum::AWAITING_LOCATION => $this->handleAwaitingLocation($session, $messageType, $message),
                WhatsAppSessionStateEnum::AWAITING_EXPAND_CHOICE => $this->handleAwaitingExpandChoice($session, $messageType, $messageBody),
                WhatsAppSessionStateEnum::SHOWING_VENDORS => $this->handleShowingVendors($session, $messageType, $messageBody),
                WhatsAppSessionStateEnum::SHOWING_PRODUCTS => $this->handleShowingProducts($session, $messageType, $messageBody),
            };
        } finally {
            $lock->release();
        }
    }

    // -------------------------------------------------------------------------
    // State handlers
    // -------------------------------------------------------------------------

    private function handleIdle(WhatsAppSession $session, string $messageType, mixed $messageBody): void
    {
        if ($messageType !== 'text' || ! is_string($messageBody) || trim($messageBody) === '') {
            $this->apiService->sendText($session->phone_number, 'What product or service are you looking for?');

            return;
        }

        $query = mb_substr(trim($messageBody), 0, self::SEARCH_QUERY_MAX_LENGTH);

        if (mb_strlen($query) < self::SEARCH_QUERY_MIN_LENGTH) {
            $this->apiService->sendText(
                $session->phone_number,
                'Please type at least '.self::SEARCH_QUERY_MIN_LENGTH.' characters to search.'
            );

            return;
        }

        $session->data = ['query' => $query];
        $session->state = WhatsAppSessionStateEnum::AWAITING_LOCATION;
        $this->sessionRepository->save($session);

        $this->interactionRepository->log([
            'phone_number' => $session->phone_number,
            'event_type' => WhatsAppInteractionEventEnum::SEARCH,
            'search_query' => $query,
        ]);

        $this->apiService->sendText(
            $session->phone_number,
            'Got it! Now please share your location so I can find vendors near you.'
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleAwaitingLocation(WhatsAppSession $session, string $messageType, array $message): void
    {
        if ($messageType !== 'location') {
            $this->apiService->sendText(
                $session->phone_number,
                'Please share your location using the attachment icon in WhatsApp and selecting Location.'
            );

            return;
        }

        $lat = (float) ($message['location']['latitude'] ?? 0);
        $lng = (float) ($message['location']['longitude'] ?? 0);
        $query = (string) data_get($session->data, 'query', '');

        $vendors = $this->vendorService->findNearbyByQuery($lat, $lng, $query);

        if ($vendors->isEmpty()) {
            $this->interactionRepository->log([
                'phone_number' => $session->phone_number,
                'event_type' => WhatsAppInteractionEventEnum::NO_RESULTS,
                'search_query' => $query,
                'buyer_latitude' => $lat,
                'buyer_longitude' => $lng,
            ]);

            $session->data = [
                'query' => $query,
                'buyer_lat' => $lat,
                'buyer_lng' => $lng,
                'searched_radius' => 5,
            ];
            $session->state = WhatsAppSessionStateEnum::AWAITING_EXPAND_CHOICE;
            $this->sessionRepository->save($session);

            $this->apiService->sendText(
                $session->phone_number,
                "No vendors found within 5 km for \"{$query}\".\n\nWant to expand the search?\n• Reply *10*, *20*, *50*, or *100* for a wider km range\n• Reply *ANY* to see all available vendors selling this product\n• Type MENU to start a new search"
            );

            return;
        }

        // Build summaries to cache in session (avoids re-querying for BACK)
        $vendorSummaries = $vendors->values()->map(fn (User $vendor) => [
            'id' => $vendor->id,
            'name' => $vendor->business_name ?? $vendor->name,
            'distance' => isset($vendor->distance_km) ? number_format((float) $vendor->distance_km, 1).' km away' : '',
            'products' => ($vendor->active_products_count ?? 0).' products',
        ])->all();

        $session->data = [
            'query' => $query,
            'buyer_lat' => $lat,
            'buyer_lng' => $lng,
            'vendor_summaries' => $vendorSummaries,
        ];
        $session->state = WhatsAppSessionStateEnum::SHOWING_VENDORS;
        $this->sessionRepository->save($session);

        $now = now()->toDateTimeString();
        $interactionLogs = $vendors->map(fn (User $vendor) => [
            'phone_number' => $session->phone_number,
            'event_type' => WhatsAppInteractionEventEnum::VENDOR_VIEWED->value,
            'search_query' => $query,
            'vendor_id' => $vendor->id,
            'buyer_latitude' => $lat,
            'buyer_longitude' => $lng,
            'created_at' => $now,
        ])->all();

        WhatsAppInteraction::insert($interactionLogs);

        foreach ($vendors as $vendor) {
            $this->analyticsService->recordProfileView(
                vendorId: $vendor->id,
                viewerId: null,
                ipAddress: null,
                source: ViewSourceEnum::WHATSAPP,
            );
        }

        $this->sendVendorList($session->phone_number, $vendorSummaries, $query);
    }

    private function handleAwaitingExpandChoice(WhatsAppSession $session, string $messageType, mixed $messageBody): void
    {
        $text = is_string($messageBody) ? strtoupper(trim($messageBody)) : '';
        $query = (string) data_get($session->data, 'query', '');
        $lat = (float) data_get($session->data, 'buyer_lat', 0);
        $lng = (float) data_get($session->data, 'buyer_lng', 0);

        if ($query === '') {
            $this->sessionRepository->resetSession($session);
            $this->apiService->sendText($session->phone_number, 'Session expired. Type MENU to start a new search.');

            return;
        }

        if ($text === 'ANY') {
            $vendors = $this->vendorService->findByQuery($query);

            if ($vendors->isEmpty()) {
                $this->sessionRepository->resetSession($session);
                $this->apiService->sendText(
                    $session->phone_number,
                    "No vendors found selling \"{$query}\" anywhere on the platform right now. Try a different product name or type MENU to start over."
                );

                return;
            }

            $vendorSummaries = $vendors->values()->map(fn (User $vendor) => [
                'id' => $vendor->id,
                'name' => $vendor->business_name ?? $vendor->name,
                'distance' => '',
                'products' => ($vendor->active_products_count ?? 0).' products',
            ])->all();

            $session->data = array_merge($session->data ?? [], ['vendor_summaries' => $vendorSummaries]);
            $session->state = WhatsAppSessionStateEnum::SHOWING_VENDORS;
            $this->sessionRepository->save($session);

            $this->sendVendorList($session->phone_number, $vendorSummaries, $query, locationless: true);

            return;
        }

        $allowedRadii = [10, 20, 50, 100];
        $radius = is_numeric($text) ? (int) $text : null;

        if ($radius === null || ! in_array($radius, $allowedRadii, true)) {
            $this->apiService->sendText(
                $session->phone_number,
                'Please reply with *10*, *20*, *50*, or *100* (km) to expand the search, or *ANY* to see all vendors, or MENU to start over.'
            );

            return;
        }

        $vendors = $this->vendorService->findNearbyByQuery($lat, $lng, $query, (float) $radius);

        if ($vendors->isEmpty()) {
            $session->data = array_merge($session->data ?? [], ['searched_radius' => $radius]);
            $this->sessionRepository->save($session);

            $this->apiService->sendText(
                $session->phone_number,
                "Still no vendors found within {$radius} km for \"{$query}\".\n\n• Reply *ANY* to see all vendors selling this product\n• Or try a larger range: ".implode(', ', array_filter($allowedRadii, fn ($r) => $r > $radius))."\n• Type MENU to start over"
            );

            return;
        }

        $vendorSummaries = $vendors->values()->map(fn (User $vendor) => [
            'id' => $vendor->id,
            'name' => $vendor->business_name ?? $vendor->name,
            'distance' => isset($vendor->distance_km) ? number_format((float) $vendor->distance_km, 1).' km away' : '',
            'products' => ($vendor->active_products_count ?? 0).' products',
        ])->all();

        $now = now()->toDateTimeString();
        WhatsAppInteraction::insert($vendors->map(fn (User $vendor) => [
            'phone_number' => $session->phone_number,
            'event_type' => WhatsAppInteractionEventEnum::VENDOR_VIEWED->value,
            'search_query' => $query,
            'vendor_id' => $vendor->id,
            'buyer_latitude' => $lat ?: null,
            'buyer_longitude' => $lng ?: null,
            'created_at' => $now,
        ])->all());

        $session->data = array_merge($session->data ?? [], ['vendor_summaries' => $vendorSummaries]);
        $session->state = WhatsAppSessionStateEnum::SHOWING_VENDORS;
        $this->sessionRepository->save($session);

        $this->sendVendorList($session->phone_number, $vendorSummaries, $query);
    }

    private function handleShowingVendors(WhatsAppSession $session, string $messageType, mixed $messageBody): void
    {
        $text = is_string($messageBody) ? strtoupper(trim($messageBody)) : '';

        if ($text === 'BACK') {
            $this->apiService->sendText(
                $session->phone_number,
                'You are already at the vendor list. Type MENU to start a new search.'
            );

            return;
        }

        // Accept interactive list reply ID (position as string) or typed number
        $position = null;
        if ($messageType === 'interactive' && is_string($messageBody) && is_numeric($messageBody)) {
            $position = (int) $messageBody;
        } elseif (is_numeric($text)) {
            $position = (int) $text;
        }

        /** @var array<int, array<string, mixed>> $summaries */
        $summaries = data_get($session->data, 'vendor_summaries', []);

        if ($position === null || $position < 1 || $position > count($summaries)) {
            $this->apiService->sendText(
                $session->phone_number,
                'Please reply with the vendor number (1–'.count($summaries).'), or type MENU to start over.'
            );

            return;
        }

        $summary = $summaries[$position - 1];
        $vendorId = (int) $summary['id'];

        try {
            $vendor = $this->vendorService->getVendorById($vendorId);
        } catch (\Throwable) {
            $this->apiService->sendText($session->phone_number, 'Vendor not found. Type MENU to start a new search.');

            return;
        }

        $products = $vendor->products()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(self::PRODUCTS_PER_PAGE, ['*'], 'page', 1);

        $session->data = array_merge($session->data ?? [], [
            'selected_vendor_id' => $vendorId,
            'product_page' => 1,
        ]);
        $session->state = WhatsAppSessionStateEnum::SHOWING_PRODUCTS;
        $this->sessionRepository->save($session);

        $this->interactionRepository->log([
            'phone_number' => $session->phone_number,
            'event_type' => WhatsAppInteractionEventEnum::PRODUCT_CATALOGUE_VIEWED,
            'vendor_id' => $vendorId,
            'buyer_latitude' => data_get($session->data, 'buyer_lat'),
            'buyer_longitude' => data_get($session->data, 'buyer_lng'),
        ]);

        $totalPages = $products->lastPage();
        $nav = $totalPages > 1 ? ' Reply NEXT for more.' : '';
        $body = "Products from *{$vendor->business_name}* (page 1/{$totalPages}).{$nav}\nReply CONTACT for their WhatsApp number.";

        /** @var \Illuminate\Support\Collection<int, Product> $items */
        $items = collect($products->items());

        $this->apiService->sendList(
            to: $session->phone_number,
            header: mb_substr((string) $vendor->business_name, 0, 60),
            body: $body,
            rows: $items->map(fn (Product $product) => [
                'id' => (string) $product->id,
                'title' => mb_substr((string) $product->name, 0, 24),
                'description' => $this->productDescription($product),
            ])->all(),
        );
    }

    private function handleShowingProducts(WhatsAppSession $session, string $messageType, mixed $messageBody): void
    {
        $text = is_string($messageBody) ? strtoupper(trim($messageBody)) : '';
        $vendorId = data_get($session->data, 'selected_vendor_id') !== null
            ? (int) data_get($session->data, 'selected_vendor_id')
            : null;
        $page = data_get($session->data, 'product_page') !== null
            ? (int) data_get($session->data, 'product_page')
            : 1;

        if ($text === 'BACK') {
            $session->state = WhatsAppSessionStateEnum::SHOWING_VENDORS;
            $this->sessionRepository->save($session);

            /** @var array<int, array<string, mixed>> $summaries */
            $summaries = data_get($session->data, 'vendor_summaries', []);
            $query = (string) data_get($session->data, 'query', '');
            $this->sendVendorList($session->phone_number, $summaries, $query);

            return;
        }

        if ($text === 'CONTACT') {
            $this->handleContactRequest($session, $vendorId);

            return;
        }

        if (in_array($text, ['NEXT', 'MORE'], true)) {
            $this->sendProductPage($session, $vendorId, $page + 1);

            return;
        }

        if ($text === 'PREV') {
            $this->sendProductPage($session, $vendorId, max(1, $page - 1));

            return;
        }

        $this->apiService->sendText($session->phone_number, $this->productsHelpMessage());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function handleContactRequest(WhatsAppSession $session, ?int $vendorId): void
    {
        if ($vendorId === null) {
            $this->sessionRepository->resetSession($session);
            $this->apiService->sendText($session->phone_number, 'Session expired. Type MENU to start over.');

            return;
        }

        try {
            $vendor = $this->vendorService->getVendorById($vendorId);
        } catch (\Throwable) {
            $this->sessionRepository->resetSession($session);
            $this->apiService->sendText($session->phone_number, 'Vendor not found. Type MENU to start a new search.');

            return;
        }

        if (empty($vendor->whatsapp_number)) {
            Log::warning('WhatsApp contact requested but vendor has no whatsapp_number set', ['vendor_id' => $vendorId]);
            $this->apiService->sendText(
                $session->phone_number,
                "This vendor hasn't set up a WhatsApp contact yet. Try another vendor or type MENU to search again."
            );

            return;
        }

        $this->interactionRepository->log([
            'phone_number' => $session->phone_number,
            'event_type' => WhatsAppInteractionEventEnum::CONTACT_REQUESTED,
            'vendor_id' => $vendorId,
        ]);

        $waNumber = $this->normalizeWhatsAppNumber((string) $vendor->whatsapp_number);
        $waLink = "https://wa.me/{$waNumber}";

        $this->apiService->sendText(
            $session->phone_number,
            "Here is *{$vendor->business_name}*'s WhatsApp:\n{$waLink}\n\nTap the link to chat directly. Type MENU to search for another vendor."
        );

        $this->sessionRepository->resetSession($session);
    }

    private function sendProductPage(WhatsAppSession $session, ?int $vendorId, int $page): void
    {
        if ($vendorId === null) {
            $this->sessionRepository->resetSession($session);
            $this->apiService->sendText($session->phone_number, 'Session expired. Type MENU to start over.');

            return;
        }

        try {
            $vendor = $this->vendorService->getVendorById($vendorId);
        } catch (\Throwable) {
            $this->sessionRepository->resetSession($session);
            $this->apiService->sendText($session->phone_number, 'Vendor not found. Type MENU to start over.');

            return;
        }

        $products = $vendor->products()
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(self::PRODUCTS_PER_PAGE, ['*'], 'page', $page);

        if ($products->isEmpty()) {
            $this->apiService->sendText($session->phone_number, 'No more products on that page. Type PREV to go back.');

            return;
        }

        $session->data = array_merge($session->data ?? [], ['product_page' => $page]);
        $this->sessionRepository->save($session);

        $totalPages = $products->lastPage();
        $nav = [];
        if ($page < $totalPages) {
            $nav[] = 'NEXT';
        }
        if ($page > 1) {
            $nav[] = 'PREV';
        }
        $navText = ! empty($nav) ? ' Type '.implode(' or ', $nav).' to navigate.' : '';
        $body = "Products from *{$vendor->business_name}* (page {$page}/{$totalPages}).{$navText}\nReply CONTACT for their WhatsApp number.";

        /** @var \Illuminate\Support\Collection<int, Product> $items */
        $items = collect($products->items());

        $this->apiService->sendList(
            to: $session->phone_number,
            header: mb_substr((string) $vendor->business_name, 0, 60),
            body: $body,
            rows: $items->map(fn (Product $product) => [
                'id' => (string) $product->id,
                'title' => mb_substr((string) $product->name, 0, 24),
                'description' => $this->productDescription($product),
            ])->all(),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $summaries
     */
    private function sendVendorList(string $to, array $summaries, string $query, bool $locationless = false): void
    {
        $count = count($summaries);
        $label = $query !== '' ? "matching \"{$query}\"" : 'nearby';
        $header = $locationless ? 'Available Vendors' : 'Nearby Vendors';

        $this->apiService->sendList(
            to: $to,
            header: $header,
            body: "Found {$count} vendor(s) {$label}. Select one to view their products:",
            rows: array_map(fn (array $summary, int $index) => [
                'id' => (string) ($index + 1),
                'title' => mb_substr((string) $summary['name'], 0, 24),
                'description' => implode(' · ', array_filter([
                    (string) ($summary['distance'] ?? ''),
                    (string) ($summary['products'] ?? ''),
                ])),
            ], $summaries, array_keys($summaries)),
        );
    }

    private function productDescription(Product $product): string
    {
        $price = '₦'.number_format((float) $product->price, 0);
        $stock = ((int) $product->stock) > 0 ? 'In stock' : 'Out of stock';

        return "{$price} · {$stock}";
    }

    /**
     * Normalize a WhatsApp number to E.164 format (digits only, with country code).
     *
     * wa.me and Meta's API both require E.164 format — no leading zero, no plus sign.
     * Nigerian local numbers starting with 0 are converted to 234XXXXXXXXXX.
     */
    private function normalizeWhatsAppNumber(string $number): string
    {
        $digits = (string) preg_replace('/[^0-9]/', '', $number);

        // Convert Nigerian local format: 0XXXXXXXXXX (11 digits) → 234XXXXXXXXXX
        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            $digits = '234'.substr($digits, 1);
        }

        return $digits;
    }

    private function welcomeMessage(): string
    {
        return "Welcome to Shopping Complex!\n\nTell me what product or service you're looking for and I'll find the nearest vendors for you.\n\nType HELP to see available commands.";
    }

    private function helpMessage(): string
    {
        return "Available commands:\n\n• Type a product name to search\n• 10, 20, 50, 100 — expand search radius (km) when no vendors found\n• ANY — show all vendors selling the product regardless of location\n• CONTACT — get vendor's WhatsApp number\n• NEXT — see more products\n• PREV — previous page\n• BACK — return to vendor list\n• MENU — start a new search\n• HELP — show this message";
    }

    private function productsHelpMessage(): string
    {
        return "Type CONTACT to get this vendor's WhatsApp, NEXT/PREV to browse products, BACK to see other vendors, or MENU to start a new search.";
    }
}
