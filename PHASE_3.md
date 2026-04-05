# Phase 3 — Analytics

## Goal
Surface the WhatsApp interaction data collected in Phase 2 on the vendor analytics dashboard and the admin panel. Vendors should see how buyers are discovering them through the bot. Admins should see platform-wide bot health and usage. No new database tables are needed — all data already exists in `whatsapp_interactions`, `profile_views`, and `vendor_subscriptions`.

---

## Context: What Already Exists

**Backend:**
- `AnalyticsRepository` — queries `profile_views`, `product_views`, `conversations`; all vendor-scoped
- `AnalyticsService` — wraps the repository; exposes `getOverview()`, `getChatContactMetrics()`, `getProfileViewMetrics()`, `getTopProducts()`, `resolveDateRange()`
- `AnalyticsController` — renders `Vendor/Analytics` via Inertia; accepts `period`, `start_date`, `end_date` query params; returns JSON when `wantsJson()`
- `AdminAnalyticsService` — `getPlatformStats()` (user/vendor counts), `getUserList()`, `getPendingVendors()`
- `AdminController` — `stats()`, `users()`, `pendingVendors()`, approve/reject vendor actions

**Routes already registered:**
- `GET /vendor/analytics` → `AnalyticsController@index`
- `GET /admin/dashboard` → `AdminController@stats`

**What Phase 3 adds:** WhatsApp metrics to the vendor analytics page, bot usage stats on the admin dashboard, and a new admin bot monitor page.

---

## Task 1 — WhatsApp Analytics Repository Methods

### Modify `modules/Repositories/AnalyticsRepository.php`

Add six new methods that query `whatsapp_interactions`. All are vendor-scoped and date-ranged, matching the pattern of existing methods.

**Methods to add:**

```php
/**
 * Count how many times this vendor appeared in WhatsApp search results (VENDOR_VIEWED events).
 */
public function getWhatsAppSearchAppearances(int $vendorId, Carbon $startDate, Carbon $endDate): int

/**
 * Count how many buyers viewed this vendor's product catalogue via WhatsApp.
 */
public function getWhatsAppCatalogueViews(int $vendorId, Carbon $startDate, Carbon $endDate): int

/**
 * Count how many buyers requested this vendor's contact via WhatsApp.
 */
public function getWhatsAppContactRequests(int $vendorId, Carbon $startDate, Carbon $endDate): int

/**
 * Get daily WhatsApp search appearances for chart rendering.
 *
 * @return Collection<int, \stdClass>  Each item has: date (Y-m-d string), count (int)
 */
public function getWhatsAppSearchAppearancesByDate(int $vendorId, Carbon $startDate, Carbon $endDate): Collection

/**
 * Get top search terms that triggered this vendor appearing in results.
 *
 * Queries VENDOR_VIEWED events for this vendor, groups by search_query, orders by frequency.
 *
 * @return Collection<int, \stdClass>  Each item has: search_query (string), count (int)
 */
public function getTopWhatsAppSearchQueries(int $vendorId, Carbon $startDate, Carbon $endDate, int $limit = 10): Collection

/**
 * Break down profile views by source (web vs whatsapp) for this vendor.
 *
 * @return Collection<int, \stdClass>  Each item has: source (string), count (int)
 */
public function getProfileViewsBySource(int $vendorId, Carbon $startDate, Carbon $endDate): Collection
```

**Implementation notes:**
- All methods query `whatsapp_interactions` table except `getProfileViewsBySource` which queries `profile_views`
- Filter by `event_type` using the enum value (e.g. `WhatsAppInteractionEventEnum::VENDOR_VIEWED->value`)
- Use `DB::table('whatsapp_interactions')` — no need to import the Eloquent model here
- `getWhatsAppSearchAppearancesByDate` uses `selectRaw('DATE(created_at) as date, COUNT(*) as count')` grouped by `DATE(created_at)`, ordered by `date` — same pattern as `getProfileViewsByDate()`
- `getTopWhatsAppSearchQueries` filters `search_query IS NOT NULL`, groups by `search_query`, orders by `count` desc, applies `$limit`

---

## Task 2 — AnalyticsService: WhatsApp Metrics Method

### Modify `modules/Services/AnalyticsService.php`

Add one new public method that aggregates all WhatsApp metrics for a vendor:

```php
/**
 * Get WhatsApp discovery metrics for the vendor analytics dashboard.
 *
 * @return array<string, mixed>
 */
public function getWhatsAppMetrics(int $vendorId, Carbon $startDate, Carbon $endDate): array
{
    return [
        'search_appearances' => $this->analyticsRepository->getWhatsAppSearchAppearances($vendorId, $startDate, $endDate),
        'catalogue_views'    => $this->analyticsRepository->getWhatsAppCatalogueViews($vendorId, $startDate, $endDate),
        'contact_requests'   => $this->analyticsRepository->getWhatsAppContactRequests($vendorId, $startDate, $endDate),
        'daily_appearances'  => $this->analyticsRepository->getWhatsAppSearchAppearancesByDate($vendorId, $startDate, $endDate),
        'top_search_queries' => $this->analyticsRepository->getTopWhatsAppSearchQueries($vendorId, $startDate, $endDate),
        'profile_views_by_source' => $this->analyticsRepository->getProfileViewsBySource($vendorId, $startDate, $endDate),
        'period' => [
            'start_date' => $startDate->toDateString(),
            'end_date'   => $endDate->toDateString(),
        ],
    ];
}
```

No other changes to `AnalyticsService`.

---

## Task 3 — AnalyticsController: Surface WhatsApp Data

### Modify `modules/Http/Controllers/AnalyticsController.php`

In the `index()` method, add a `getWhatsAppMetrics()` call alongside the existing calls, and include it in both the Inertia render and the JSON response:

```php
$whatsAppMetrics = $this->analyticsService->getWhatsAppMetrics($user->id, $startDate, $endDate);

$data = [
    'overview'        => $overview,
    'chatContacts'    => $chatContacts,
    'profileViews'    => $profileViews,
    'topProducts'     => $topProducts,
    'whatsAppMetrics' => $whatsAppMetrics,  // ← new
    'subscription'    => [...],
];
```

No other changes to this controller.

---

## Task 4 — AdminAnalyticsService: Platform Bot Stats

### Modify `modules/Services/AdminAnalyticsService.php`

Add two new public methods.

**Method 1 — `getPlatformBotStats(): array`**

Returns platform-wide WhatsApp bot usage stats. Uses `DB::table('whatsapp_interactions')` directly.

```php
/**
 * Get platform-wide WhatsApp bot statistics for the admin dashboard.
 *
 * @return array<string, mixed>
 */
public function getPlatformBotStats(): array
```

Returns:
```php
[
    'total_searches'        => int,   // COUNT of event_type = 'search' all time
    'total_contacts_made'   => int,   // COUNT of event_type = 'contact_requested' all time
    'total_no_results'      => int,   // COUNT of event_type = 'no_results' all time
    'searches_this_month'   => int,   // COUNT of 'search' events in current calendar month
    'contacts_this_month'   => int,   // COUNT of 'contact_requested' events in current calendar month
    'active_subscribed_vendors' => int, // COUNT of vendor_subscriptions where status=active AND expires_at > now()
    'monthly_revenue'       => float, // SUM of amount_paid from vendor_subscriptions where status=active AND expires_at > now()
]
```

Query `vendor_subscriptions` for subscription stats. Use `VendorSubscriptionStatusEnum::ACTIVE->value` for the status filter.

**Method 2 — `getRecentInteractions(int $perPage = 50): LengthAwarePaginator`**

Returns a paginated list of recent `whatsapp_interactions` for the bot monitor page, joined with the vendor's business name.

```php
/**
 * Get paginated recent WhatsApp interactions for the bot monitor.
 *
 * @return LengthAwarePaginator<\stdClass>
 */
public function getRecentInteractions(int $perPage = 50): LengthAwarePaginator
```

Query:
- Join `whatsapp_interactions` with `users` on `vendor_id = users.id` (LEFT JOIN — many events have no vendor)
- Select: `whatsapp_interactions.id`, `phone_number`, `event_type`, `search_query`, `vendor_id`, `users.business_name as vendor_name`, `buyer_latitude`, `buyer_longitude`, `whatsapp_interactions.created_at`
- Order by `whatsapp_interactions.created_at` descending
- Use `DB::table('whatsapp_interactions')` with manual pagination (`->paginate($perPage)`)

**Phone number masking:** Do NOT mask here — masking belongs in the frontend or a dedicated transformer. Return the raw value and let the React component display only the last 4 digits.

---

## Task 5 — AdminController: Extend Stats and Add Bot Monitor

### Modify `modules/Http/Controllers/Admin/AdminController.php`

**Change 1 — extend `stats()`:**

Inject `AdminAnalyticsService` already in the constructor. Add a `getPlatformBotStats()` call:

```php
$data = [
    ...$this->adminAnalyticsService->getPlatformStats(),
    'botStats' => $this->adminAnalyticsService->getPlatformBotStats(),  // ← new
];
```

**Change 2 — add `botMonitor()` action:**

```php
public function botMonitor(Request $request): Response|JsonResponse
{
    $perPage = min(max((int) $request->get('per_page', 50), 1), 100);
    $data = ['interactions' => $this->adminAnalyticsService->getRecentInteractions($perPage)];

    if ($request->wantsJson()) {
        return response()->json($data);
    }

    return Inertia::render('Admin/BotMonitor', $data);
}
```

---

## Task 6 — Route: Admin Bot Monitor

### Modify `routes/web.php`

Add one route inside the existing `auth + admin + throttle:auth` group:

```php
Route::get('/bot-monitor', [AdminController::class, 'botMonitor'])->name('admin.bot.monitor');
```

---

## Task 7 — Frontend: Vendor Analytics Page

### New File — `resources/js/Pages/Vendor/Analytics.tsx`

Renders the vendor analytics dashboard. Receives all props from `AnalyticsController@index`.

**TypeScript prop types:**

```ts
type DailyDataPoint = { date: string; count: number }
type TopProduct = { product_id: number; name: string; price: string; views_count: number }
type TopSearchQuery = { search_query: string; count: number }
type SourceBreakdown = { source: string; count: number }

type Props = {
  overview: {
    chat_contacts: number
    profile_views: number
    product_views: number
    average_view_value: number
    followers_count: number
    active_products: number
    period: { start_date: string; end_date: string }
  }
  chatContacts: { total: number; daily: DailyDataPoint[]; period: object }
  profileViews: { total: number; daily: DailyDataPoint[]; period: object }
  topProducts: { products: TopProduct[]; average_view_value: number; period: object }
  whatsAppMetrics: {
    search_appearances: number
    catalogue_views: number
    contact_requests: number
    daily_appearances: DailyDataPoint[]
    top_search_queries: TopSearchQuery[]
    profile_views_by_source: SourceBreakdown[]
    period: object
  }
  subscription: {
    plan_name: string | null
    plan_slug: string | null
    expires_at: string | null
    days_remaining: number | null
    product_limit: number | null
  }
}
```

**Page layout (sections in order):**

1. **Period filter bar** — buttons for Daily / Weekly / Monthly / Yearly. Clicking updates the URL query param (`?period=weekly`) and makes an AJAX fetch to reload data without a full page load. The controller already supports `wantsJson()`.

2. **Overview cards row** — 4 cards: Profile Views, Product Views, Followers, Active Products. Numbers from `overview`.

3. **Subscription status banner** — shows plan name, expiry date, and days remaining. Links to `/vendor/subscription`. If on Free plan, no expiry shown.

4. **WhatsApp Discovery section** — heading: "WhatsApp Discovery". Three metric cards:
   - "Search Appearances" — `whatsAppMetrics.search_appearances` — subtitle: "Times you appeared in buyer searches"
   - "Catalogue Views" — `whatsAppMetrics.catalogue_views` — subtitle: "Buyers who viewed your products"
   - "Contact Requests" — `whatsAppMetrics.contact_requests` — subtitle: "Buyers who requested your WhatsApp"

5. **WhatsApp daily appearances chart** — simple bar or line chart over `whatsAppMetrics.daily_appearances`. Use a lightweight chart library already in the project, or plain SVG/CSS bars if none exists.

6. **Top Search Queries table** — table with columns: Rank, Search Term, Times Found. Sourced from `whatsAppMetrics.top_search_queries`. Max 10 rows. Empty state: "No searches yet."

7. **Profile Views by Source table** — small 2-row table: Web | WhatsApp | counts from `whatsAppMetrics.profile_views_by_source`.

8. **Top Products section** — table: Product Name, Price, Views. Sourced from `topProducts.products`. Empty state: "No product views yet."

**Key implementation notes:**
- Use `router.get` from `@inertiajs/react` for the period filter to avoid full page reloads where possible, or fall back to `fetch()` + local state for the JSON endpoint
- Format Nigerian prices as `₦{amount.toLocaleString()}`
- Mask all phone numbers that may appear — none on this page, but establish the convention

---

## Task 8 — Frontend: Admin Dashboard Enhancement

### New File — `resources/js/Pages/Admin/Dashboard.tsx`

Renders the admin dashboard. Receives props from `AdminController@stats`.

**TypeScript prop types:**

```ts
type Props = {
  users: { total: number; admins: number; vendors: number; customers: number }
  products: { total: number }
  vendors: { approved: number; pending_review: number; rejected: number; draft: number }
  botStats: {
    total_searches: number
    total_contacts_made: number
    total_no_results: number
    searches_this_month: number
    contacts_this_month: number
    active_subscribed_vendors: number
    monthly_revenue: number
  }
}
```

**Page layout:**

1. **Users & Vendors stat cards** — Total Users, Total Vendors (approved), Pending Applications, Total Products.

2. **WhatsApp Bot Stats section** — heading: "WhatsApp Bot". Cards:
   - "Total Searches" — `botStats.total_searches` — subtitle: `botStats.searches_this_month` + " this month"
   - "Connections Made" — `botStats.total_contacts_made` — subtitle: `botStats.contacts_this_month` + " this month"
   - "No Results" — `botStats.total_no_results` — subtitle: "Searches with no vendor found"
   - "Active Subscribed Vendors" — `botStats.active_subscribed_vendors`
   - "Monthly Revenue" — `₦{botStats.monthly_revenue.toLocaleString()}` — subtitle: "Active subscriptions"

3. **Quick links** — Pending Vendor Applications (badge with count), Bot Monitor.

4. **Pending vendor applications list** — not on this page (separate `Admin/Vendors` page). Just link to it.

---

## Task 9 — Frontend: Admin Bot Monitor Page

### New File — `resources/js/Pages/Admin/BotMonitor.tsx`

Renders the bot interaction log. Receives paginated interactions from `AdminController@botMonitor`.

**TypeScript prop types:**

```ts
type Interaction = {
  id: number
  phone_number: string      // display only last 4 digits: '***' + phone_number.slice(-4)
  event_type: string
  search_query: string | null
  vendor_id: number | null
  vendor_name: string | null
  buyer_latitude: number | null
  buyer_longitude: number | null
  created_at: string
}

type Props = {
  interactions: {
    data: Interaction[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}
```

**Page layout:**

1. **Page heading** — "Bot Monitor" with subtitle showing total interaction count.

2. **Interactions table** — columns:
   - Phone — masked: `***` + last 4 digits
   - Event — styled badge per event type:
     - `search` → neutral/blue
     - `vendor_viewed` → green
     - `product_catalogue_viewed` → teal
     - `contact_requested` → purple (positive outcome)
     - `no_results` → amber (needs attention)
   - Search Query — truncated to 40 chars with title tooltip for full text; "-" if null
   - Vendor — vendor business name or "-" if no vendor involved
   - Time — relative timestamp (e.g. "3 minutes ago") with ISO tooltip on hover

3. **Pagination** — previous/next page links using Inertia's `router.get` with `page` query param.

4. **Empty state** — "No interactions yet." shown when `interactions.data` is empty.

**Privacy note:** Phone number masking must happen in the component — never display the raw number. The `phone_number` prop is present for reference, but the rendered output must always be `***XXXX`.

---

## Summary of Modified Files

| File | What Changes |
|---|---|
| `modules/Repositories/AnalyticsRepository.php` | Add 6 WhatsApp query methods |
| `modules/Services/AnalyticsService.php` | Add `getWhatsAppMetrics()` |
| `modules/Http/Controllers/AnalyticsController.php` | Pass `whatsAppMetrics` to Inertia/JSON |
| `modules/Services/AdminAnalyticsService.php` | Add `getPlatformBotStats()` and `getRecentInteractions()` |
| `modules/Http/Controllers/Admin/AdminController.php` | Extend `stats()`, add `botMonitor()` |
| `routes/web.php` | Add `GET /admin/bot-monitor` route |

## Summary of New Files

| File | Purpose |
|---|---|
| `resources/js/Pages/Vendor/Analytics.tsx` | Vendor analytics dashboard with WhatsApp discovery section |
| `resources/js/Pages/Admin/Dashboard.tsx` | Admin dashboard with bot stats |
| `resources/js/Pages/Admin/BotMonitor.tsx` | Paginated bot interaction log |
