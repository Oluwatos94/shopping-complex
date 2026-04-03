# Phase 2 — WhatsApp Bot (MVP)

## Goal
Build the WhatsApp bot that powers buyer discovery. A buyer messages the Shopping Complex WhatsApp number, searches for a product, shares their location, browses nearby vendors and their catalogues, then gets connected directly to a vendor on WhatsApp. Every interaction is logged for vendor analytics.

---

## Task 1 — New Database Tables

### Migration 1 — `whatsapp_sessions` table
Tracks the conversation state per buyer phone number. One active session per phone number at a time.

Columns:
- `id` — primary key
- `phone_number` — string(20), unique — the buyer's WhatsApp number (E.164 format, e.g. `+2348012345678`)
- `state` — enum: `idle`, `awaiting_location`, `showing_vendors`, `showing_products` — default `idle`
- `data` — JSON, nullable — session context: search query, cached vendor list, selected vendor ID, product page number
- `last_active_at` — timestamp — updated on every message; used to expire stale sessions
- `timestamps`
- Index on `last_active_at` (for the session expiry query)

### Migration 2 — `whatsapp_interactions` table
An append-only log of every bot event. Used for vendor analytics in Phase 3.

Columns:
- `id` — primary key
- `phone_number` — string(20) — buyer's number, not a foreign key (buyers have no account)
- `event_type` — enum: `search`, `vendor_viewed`, `product_catalogue_viewed`, `contact_requested`, `no_results` — not nullable
- `search_query` — string, nullable — what the buyer searched for
- `vendor_id` — foreign key to `users.id`, nullable, `onDelete('set null')` — the vendor involved in this event
- `product_id` — foreign key to `products.id`, nullable, `onDelete('set null')` — relevant for `product_catalogue_viewed`
- `buyer_latitude` — decimal(10,7), nullable — buyer's shared location
- `buyer_longitude` — decimal(10,7), nullable
- `created_at` — timestamp only (no `updated_at` — this is an immutable log)
- Index on `vendor_id` (for per-vendor analytics queries)
- Index on `event_type` + `created_at` (for aggregation queries in Phase 3)

> Use `$table->timestamp('created_at')->useCurrent()` and skip `updated_at` entirely (`$table->timestamps()` replaced with just `$table->timestamp('created_at')->useCurrent()`).

---

## Task 2 — New Models

### Model 1 — `modules/Models/WhatsAppSession.php`
- Namespace: `ModulesShoppingComplex\Models`
- `$fillable`: `phone_number`, `state`, `data`, `last_active_at`
- Cast `data` to `array`
- Cast `last_active_at` to `datetime`
- Cast `state` to the new `WhatsAppSessionStateEnum` (see below)
- No relationships needed

### Model 2 — `modules/Models/WhatsAppInteraction.php`
- Namespace: `ModulesShoppingComplex\Models`
- `$fillable`: `phone_number`, `event_type`, `search_query`, `vendor_id`, `product_id`, `buyer_latitude`, `buyer_longitude`
- `public $timestamps = false;` — only `created_at`
- `protected $guarded = []` or just add `created_at` to fillable
- Cast `event_type` to `WhatsAppInteractionEventEnum` (see below)
- Cast `buyer_latitude` and `buyer_longitude` to `float`
- Relationships:
  - `vendor(): BelongsTo` → `User::class`, foreign key `vendor_id`
  - `product(): BelongsTo` → `Product::class`

### New Enums

**`modules/Models/Enums/WhatsAppSessionStateEnum.php`**
```
IDLE = 'idle'
AWAITING_LOCATION = 'awaiting_location'
SHOWING_VENDORS = 'showing_vendors'
SHOWING_PRODUCTS = 'showing_products'
```

**`modules/Models/Enums/WhatsAppInteractionEventEnum.php`**
```
SEARCH = 'search'
VENDOR_VIEWED = 'vendor_viewed'
PRODUCT_CATALOGUE_VIEWED = 'product_catalogue_viewed'
CONTACT_REQUESTED = 'contact_requested'
NO_RESULTS = 'no_results'
```

Both use the `EnumToArray` trait (same as all other enums in the codebase).

---

## Task 3 — Configuration & Routes

### Environment Variables — `.env` and `config/services.php`
Add three new entries to `config/services.php` under a `'whatsapp'` key:

```php
'whatsapp' => [
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token'    => env('WHATSAPP_ACCESS_TOKEN'),
    'verify_token'    => env('WHATSAPP_VERIFY_TOKEN'),
],
```

Add the three corresponding keys to `.env`.

### Routes — `routes/web.php`
Add two public routes (no auth middleware) for the Meta webhook:

```php
Route::get('/webhook/whatsapp', [WhatsAppController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppController::class, 'receive']);
```

These must be outside all middleware groups — Meta's servers cannot authenticate.

---

## Task 4 — WhatsApp API Service

### New Service — `modules/Services/WhatsAppApiService.php`
A thin HTTP client responsible for sending outbound messages via the Meta Cloud API. Uses `Http::withToken(config('services.whatsapp.access_token'))`.

Base URL: `https://graph.facebook.com/v19.0/{phone_number_id}/messages`

Methods:
- `sendText(string $to, string $body): void` — sends a plain text message
- `sendList(string $to, string $header, string $body, array $rows): void` — sends a WhatsApp list message (used for vendor list and product catalogue). Each row has `id`, `title`, `description`.
- `sendInteractive(string $to, string $body, array $buttons): void` — sends a button reply (used for BACK / MENU options)

All three methods dispatch a `SendWhatsAppMessage` job (see Task 6) rather than making the HTTP call inline, to avoid blocking the webhook response.

Registration: register `WhatsAppApiService` as a singleton in `AppServiceProvider`.

---

## Task 5 — Webhook Controller

### New Controller — `modules/Http/Controllers/WhatsAppController.php`
Two actions:

**`verify(Request $request): Response`**
Meta calls this GET endpoint to verify the webhook. Check that `$request->query('hub.verify_token')` matches `config('services.whatsapp.verify_token')`. If it matches, return `$request->query('hub.challenge')` as a plain text 200 response. Otherwise return 403.

**`receive(Request $request): Response`**
- Immediately return `response('OK', 200)` — Meta requires a fast response.
- Before returning, dispatch `ProcessWhatsAppWebhook::dispatch($request->all())`.
- Validate the payload structure minimally (check that `object === 'whatsapp_business_account'` exists) before dispatching — silently ignore malformed payloads.

---

## Task 6 — Background Jobs

### Job 1 — `modules/Jobs/ProcessWhatsAppWebhook.php`
Implements `ShouldQueue`. Receives the raw webhook payload array in its constructor.

In `handle(WhatsAppBotService $botService)`:
1. Extract the message from the nested Meta payload structure: `entry[0].changes[0].value.messages[0]`
2. If no message is present (e.g. delivery receipt), return early
3. Extract `$from` (phone number), `$messageType` (`text`, `interactive`, `location`), and `$messageBody`
4. Call `$botService->handle($from, $messageType, $messageBody, $payload)`

Use `$tries = 3` and `$backoff = 30`.

### Job 2 — `modules/Jobs/SendWhatsAppMessage.php`
Implements `ShouldQueue`. Receives the outbound payload array in its constructor.

In `handle()`:
- Makes the actual HTTP POST to the Meta Graph API using the credentials from `config('services.whatsapp')`
- Logs a warning on non-200 responses (do not throw — a failed message should not crash the job permanently)
- `$tries = 3`, `$backoff = 10`

---

## Task 7 — Bot State Machine Service

### New Service — `modules/Services/WhatsAppBotService.php`
The core of Phase 2. Reads session state, processes the incoming message, queries the right data, sends a reply, and updates the session.

Dependencies (constructor injection):
- `WhatsAppApiService`
- `WhatsAppSessionRepository` (new — see below)
- `WhatsAppInteractionRepository` (new — see below)
- `VendorService`
- `SubscriptionService` (for product catalogue)

**`handle(string $from, string $messageType, mixed $messageBody, array $payload): void`**

This is the entry point called by `ProcessWhatsAppWebhook`. It:
1. Loads or creates the session for `$from`
2. Checks if the session is expired (last_active_at older than 30 minutes) → if so, reset to `idle`
3. Checks for global commands first regardless of state: `MENU` or `HI` → reset to `idle` and show welcome; `HELP` → send help text
4. Delegates to the appropriate state handler based on `session->state`

**State handlers (private methods):**

`handleIdle(session, messageType, messageBody)`:
- Any text message is treated as a search query
- Store the query in `session->data['query']`
- Transition to `awaiting_location`
- Reply: "Got it! Now please share your location so I can find vendors near you."
- Log `WhatsAppInteraction` with `event_type = search`, `search_query = messageBody`

`handleAwaitingLocation(session, messageType, payload)`:
- Expects a location message (`messageType === 'location'`)
- Extract `latitude` and `longitude` from payload
- Call `VendorService::findNearbyByQuery($lat, $lng, $session->data['query'])` (see Task 8)
- If no vendors found: log `no_results`, reply with "No vendors found nearby for that search. Try a different product name.", reset to `idle`
- If vendors found: cache them in `session->data['vendors']` (array of vendor IDs in order), transition to `showing_vendors`
- Send the vendor list (up to 5) using `WhatsAppApiService::sendList()`
- Each entry: vendor name, distance (e.g. "1.2 km away"), rating + product count in description
- Log `vendor_viewed` interaction for each vendor shown

`handleShowingVendors(session, messageType, messageBody)`:
- Expects an interactive list reply — the `id` is the vendor's position (1–5) in the cached list
- Handle `BACK` text → reply "You are already at the vendor list. Type MENU to start a new search."
- Look up the selected vendor from `session->data['vendors']`
- Load their active products (paginated, 10 per page)
- Store selected vendor ID and current page in session data
- Transition to `showing_products`
- Send the product catalogue using `WhatsAppApiService::sendList()`
- Each entry: product name, price formatted as NGN, stock status in description
- Log `product_catalogue_viewed` interaction for the selected vendor

`handleShowingProducts(session, messageType, messageBody)`:
- Handle `BACK` text → transition back to `showing_vendors`, resend the cached vendor list
- Handle `NEXT` and `PREV` text → paginate through products, resend the product list for the new page
- Handle `CONTACT` or a button reply requesting contact → send the vendor's `whatsapp_number` as a `wa.me` link: `https://wa.me/{number}`. Reset to `idle`. Log `contact_requested`.
- Any other text → send help text listing available commands

**Session expiry**: Sessions older than 30 minutes (last_active_at) are treated as idle regardless of stored state. Reset the session when this is detected.

**After every handler**: update `session->last_active_at = now()` and save.

### New Repository — `modules/Repositories/WhatsAppSessionRepository.php`
Methods:
- `findOrCreate(string $phoneNumber): WhatsAppSession` — find by phone or create with state=idle
- `save(WhatsAppSession $session): void` — persists the session
- `resetSession(WhatsAppSession $session): void` — sets state=idle, clears data, saves

### New Repository — `modules/Repositories/WhatsAppInteractionRepository.php`
Methods:
- `log(array $data): WhatsAppInteraction` — creates a new interaction record

---

## Task 8 — Modify VendorService

### `modules/Services/VendorService.php`
Add a new method `findNearbyByQuery(float $lat, float $lng, string $query, float $radiusKm = 5.0): Collection`.

This method:
1. Calls `VendorRepository::findNearby($lat, $lng, $radiusKm)` — which already filters by active subscription (done in Phase 1)
2. Further filters the result to only vendors that have at least one active product whose `name` or `category.name` matches `$query` (case-insensitive, partial match)
3. Orders by: distance ascending first, then `search_priority` descending (subscription tier), then rating descending
4. Returns up to 5 vendors

The filtering in step 2 can be done with `whereHas('products', fn($q) => $q->where('is_active', true)->where('name', 'LIKE', "%{$query}%"))` added directly to the builder, or as a post-query filter on the returned Collection if the repository already returns a Collection. Use whichever keeps the query in the repository layer.

---

## Task 9 — Analytics: Log WhatsApp Source

### `modules/Services/AnalyticsService.php`
When the bot calls `recordProfileView()` or `recordProductView()`, it passes `ViewSourceEnum::WHATSAPP` (added in Phase 1, Task 8). No code change needed here — the parameter is already in place.

The `WhatsAppBotService` should call these methods at the right moments:
- `recordProfileView()` with `source = ViewSourceEnum::WHATSAPP` when a vendor is shown to a buyer (`vendor_viewed`)
- `recordProductView()` with `source = ViewSourceEnum::WHATSAPP` when a buyer views a product catalogue (`product_catalogue_viewed`)

These calls go inside the respective state handlers in `WhatsAppBotService`.

---

## Summary of New Files

| File | Type | Purpose |
|---|---|---|
| `database/migrations/..._create_whatsapp_sessions_table.php` | Migration | Buyer conversation state |
| `database/migrations/..._create_whatsapp_interactions_table.php` | Migration | Bot event log for analytics |
| `modules/Models/WhatsAppSession.php` | Model | Session model |
| `modules/Models/WhatsAppInteraction.php` | Model | Interaction log model |
| `modules/Models/Enums/WhatsAppSessionStateEnum.php` | Enum | Session state values |
| `modules/Models/Enums/WhatsAppInteractionEventEnum.php` | Enum | Interaction event type values |
| `modules/Services/WhatsAppApiService.php` | Service | Sends messages via Meta Cloud API |
| `modules/Services/WhatsAppBotService.php` | Service | Full bot state machine |
| `modules/Repositories/WhatsAppSessionRepository.php` | Repository | Session CRUD |
| `modules/Repositories/WhatsAppInteractionRepository.php` | Repository | Interaction log writes |
| `modules/Http/Controllers/WhatsAppController.php` | Controller | Webhook verify + receive |
| `modules/Jobs/ProcessWhatsAppWebhook.php` | Job | Processes incoming webhook off the request cycle |
| `modules/Jobs/SendWhatsAppMessage.php` | Job | Sends outbound messages off the request cycle |

## Summary of Modified Files

| File | What Changes |
|---|---|
| `modules/Services/VendorService.php` | Add `findNearbyByQuery()` method |
| `config/services.php` | Add `whatsapp` credentials block |
| `routes/web.php` | Add two public webhook routes |
| `app/Providers/AppServiceProvider.php` | Register `WhatsAppApiService` as singleton |
