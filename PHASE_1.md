# Phase 1 — Complete the Vendor Platform

## Goal
Add WhatsApp number to vendor profiles, build the subscription system with payment, enforce subscription on vendor visibility, and update the dashboard to reflect subscription status.

---

## Task 1 — Add WhatsApp Number to Users

### Migration
Create a new migration that adds a `whatsapp_number` column (nullable string) to the `users` table, positioned after the existing `phone` column.

### Model — `modules/Models/User.php`
- Add `whatsapp_number` to the `$fillable` array (line 62 area)
- Add `@property string|null $whatsapp_number` to the docblock

### Onboarding Form — `modules/Services/VendorService.php`
- In `buildOnboardingData()` (line 226), the `whatsapp_number` is on `users`, not `vendor_onboardings`. It is saved directly on the user, not the onboarding record.
- In `registerAsVendor()` (line 31), include `whatsapp_number` in the `$user->update()` call if it is provided in `$data`.
- In `submitOnboarding()` (line 118), after the onboarding record is saved, update `$user->whatsapp_number` if it was provided.

### Onboarding Request — `modules/Http/Requests/SubmitOnboardingRequest.php`
- Add `whatsapp_number` as an optional validated field (nullable, string, max 20 characters, Nigerian phone format).
- Expose it via a `getWhatsAppNumber()` method or include it in `getBusinessInfo()`.

### Onboarding Controller — `modules/Http/Controllers/VendorController.php`
- In `onboarding()` (line 258), pass the vendor's current `whatsapp_number` back to the frontend in `savedData`.
- In `submitOnboarding()` (line 340), pass `whatsapp_number` to `vendorService->submitOnboarding()`.

### Frontend — `resources/ts/pages/Vendor/Onboarding.tsx`
- Add a WhatsApp number input field in the Business Information step (Step 1).
- Label it clearly: "Business WhatsApp Number (buyers will be connected to this number)".
- It should be optional but recommended.

---

## Task 2 — Subscription Plans & Vendor Subscriptions Tables

### Migration 1 — `subscription_plans` table
Columns:
- `id` — primary key
- `name` — string (e.g. Free, Basic, Premium)
- `slug` — string, unique (e.g. free, basic, premium)
- `price` — decimal(10,2) — monthly price in NGN
- `product_limit` — integer — max products a vendor on this plan can list (use a very large number like 9999 to represent unlimited)
- `search_priority` — integer, default 0 — higher value means higher placement in bot results
- `features` — JSON — array of feature description strings for display on the pricing page
- `is_active` — boolean, default true
- `timestamps`

### Migration 2 — `vendor_subscriptions` table
Columns:
- `id` — primary key
- `vendor_id` — foreign key to `users.id`, cascade delete
- `plan_id` — foreign key to `subscription_plans.id`
- `status` — enum: `active`, `expired`, `cancelled` — default `active`
- `started_at` — timestamp
- `expires_at` — timestamp
- `payment_reference` — nullable string — the Paystack/Flutterwave transaction reference
- `amount_paid` — nullable decimal(10,2)
- `timestamps`
- Index on `vendor_id`
- Index on `expires_at` (for the expiry job)

### Seeder — `database/seeders/SubscriptionPlanSeeder.php`
Seed the three default plans:

| name | slug | price | product_limit | search_priority |
|---|---|---|---|---|
| Free | free | 0.00 | 10 | 0 |
| Basic | basic | 5000.00 | 30 | 1 |
| Premium | premium | 15000.00 | 9999 | 2 |

Register this seeder in `DatabaseSeeder.php`.

### Model 1 — `modules/Models/SubscriptionPlan.php`
- `$fillable`: name, slug, price, product_limit, search_priority, features, is_active
- Cast `features` to array
- Relationship: `hasMany(VendorSubscription::class)`
- Scope: `active()` — where `is_active = true`

### Model 2 — `modules/Models/VendorSubscription.php`
- `$fillable`: vendor_id, plan_id, status, started_at, expires_at, payment_reference, amount_paid
- Cast `started_at` and `expires_at` to datetime
- Relationships: `belongsTo(User::class, 'vendor_id')` and `belongsTo(SubscriptionPlan::class, 'plan_id')`
- Method: `isActive()` — returns true if status is active and `expires_at` is in the future

### User Model — `modules/Models/User.php`
- Add relationship: `activeSubscription()` — `hasOne(VendorSubscription::class, 'vendor_id')->where('status', 'active')->where('expires_at', '>', now())->with('plan')->latestOfMany()`
- Add relationship: `subscriptions()` — `hasMany(VendorSubscription::class, 'vendor_id')`

---

## Task 3 — Auto-assign Free Plan on Vendor Approval

### `modules/Services/VendorService.php` — `approveOnboarding()` (line 153)
After the onboarding status is updated to `APPROVED`, create a `VendorSubscription` record for the vendor on the Free plan. The Free plan has no expiry so set `expires_at` to 100 years in the future (or a sentinel date like `2099-12-31`). Set `status` to `active` and `amount_paid` to 0.

This ensures every approved vendor is immediately discoverable without needing to manually subscribe.

---

## Task 4 — Subscription Payment Flow

### New Service — `modules/Services/SubscriptionService.php`
Responsible for:
- `getPlans()` — returns all active subscription plans
- `getVendorSubscription(int $vendorId)` — returns the vendor's current active subscription with plan
- `initiatePayment(User $vendor, SubscriptionPlan $plan)` — calls Paystack to create a payment link, returns the authorization URL
- `handlePaystackCallback(string $reference)` — verifies the transaction with Paystack, then creates or updates the `VendorSubscription` record
- `cancelSubscription(User $vendor)` — marks current subscription as cancelled

Paystack integration: use `Http::withToken(config('services.paystack.secret_key'))` to call `https://api.paystack.co/transaction/initialize` for initiating and `https://api.paystack.co/transaction/verify/{reference}` for verifying. Store `PAYSTACK_SECRET_KEY` in `.env` and `config/services.php`.

### New Controller — `modules/Http/Controllers/SubscriptionController.php`
Routes and methods:
- `GET /vendor/subscription` → `index()` — shows available plans and the vendor's current plan
- `POST /vendor/subscription/{plan}` → `checkout()` — initiates Paystack payment, redirects to Paystack URL
- `GET /vendor/subscription/callback` → `callback()` — Paystack redirects here after payment; verifies and activates subscription
- `POST /vendor/subscription/cancel` → `cancel()` — cancels current subscription

### Frontend — `resources/ts/pages/Vendor/Subscription.tsx`
A page showing:
- The vendor's current active plan and its expiry date
- All three plans side by side with their features and prices
- A "Subscribe" or "Upgrade" button on each paid plan that POSTs to the checkout route
- A note on the Free plan that it is the default

### Routes — `routes/web.php`
Add these inside the authenticated + vendor middleware group:
- `GET /vendor/subscription`
- `POST /vendor/subscription/{plan}`
- `GET /vendor/subscription/callback`
- `POST /vendor/subscription/cancel`

---

## Task 5 — Enforce Subscription on Product Limits

### `modules/Http/Controllers/ProductController.php`
In `store()` (the create product endpoint), before saving a new product, check the vendor's active subscription plan's `product_limit` against their current active product count. If they are at the limit, return a validation error: "You have reached the product limit for your plan. Upgrade to add more products."

### Frontend — Product creation form
Display the vendor's current product count versus their plan's limit. Show a clear upgrade prompt when they are at or near the limit.

---

## Task 6 — Enforce Subscription on Bot Visibility

### `modules/Repositories/VendorRepository.php` — `findNearby()`
The existing nearby vendor query needs a `whereHas` condition that requires the vendor to have an active subscription (status = active AND expires_at > now()). This is what controls whether a vendor appears in WhatsApp search results. Vendors without a subscription (whose Free plan has expired, or who were never approved) should not appear.

This change is minimal but critical — it is the gate between "listed on platform" and "discoverable by buyers."

---

## Task 7 — Update Vendor Dashboard

### `modules/Http/Controllers/AnalyticsController.php` — `index()` (line 24)
Pass subscription data to the frontend alongside the existing analytics data:
- Current plan name
- Plan expiry date
- Product count vs. plan limit

### Frontend — `resources/ts/pages/Vendor/Analytics.tsx`
Add a subscription status card at the top of the dashboard showing:
- Plan name and badge
- Days remaining until renewal
- Products used / products allowed
- A link to the subscription page to upgrade

---

## Task 8 — Add Source Column to Analytics Tables

### Migration
Add a `source` column (enum: `web`, `whatsapp`, default `web`) to both `profile_views` and `product_views` tables.

### `modules/Services/AnalyticsService.php`
Update `recordProfileView()` (line 98) and `recordProductView()` (line 122) to accept an optional `string $source = 'web'` parameter and pass it when creating the record.

The WhatsApp bot (Phase 2) will call these with `source = 'whatsapp'`, which is how the vendor analytics page will later distinguish bot-driven discovery from direct web visits.

---

## Task 9 — Subscription Expiry Job

### New Job — `modules/Jobs/ExpireVendorSubscriptions.php`
A queued job that:
1. Finds all `VendorSubscription` records where `expires_at < now()` and `status = active`
2. Updates their status to `expired`
3. For each expired vendor, queues a notification (use the existing `NotificationService`) informing them their subscription has expired and prompting renewal

### Schedule — `routes/console.php`
Register the job to run daily at midnight:
`Schedule::job(ExpireVendorSubscriptions::class)->dailyAt('00:00')`

---

## Summary of New Files

| File | Type | Purpose |
|---|---|---|
| `database/migrations/..._add_whatsapp_number_to_users.php` | Migration | Adds whatsapp_number column |
| `database/migrations/..._create_subscription_plans_table.php` | Migration | subscription_plans table |
| `database/migrations/..._create_vendor_subscriptions_table.php` | Migration | vendor_subscriptions table |
| `database/migrations/..._add_source_to_views_tables.php` | Migration | Adds source column to profile_views and product_views |
| `database/seeders/SubscriptionPlanSeeder.php` | Seeder | Seeds Free, Basic, Premium plans |
| `modules/Models/SubscriptionPlan.php` | Model | subscription_plans model |
| `modules/Models/VendorSubscription.php` | Model | vendor_subscriptions model |
| `modules/Services/SubscriptionService.php` | Service | Payment initiation, verification, subscription management |
| `modules/Http/Controllers/SubscriptionController.php` | Controller | Subscription pages and payment routes |
| `modules/Jobs/ExpireVendorSubscriptions.php` | Job | Daily job to mark expired subscriptions |
| `resources/ts/pages/Vendor/Subscription.tsx` | Frontend | Subscription management page |

## Summary of Modified Files

| File | What Changes |
|---|---|
| `modules/Models/User.php` | Add whatsapp_number to fillable, add activeSubscription() and subscriptions() relationships |
| `modules/Models/VendorOnboarding.php` | No change needed (whatsapp_number lives on users, not onboardings) |
| `modules/Services/VendorService.php` | Include whatsapp_number in registerAsVendor() and submitOnboarding(), auto-assign Free plan in approveOnboarding() |
| `modules/Services/AnalyticsService.php` | Add source parameter to recordProfileView() and recordProductView() |
| `modules/Repositories/VendorRepository.php` | Add active subscription filter to findNearby() query |
| `modules/Http/Controllers/VendorController.php` | Pass whatsapp_number in onboarding savedData, pass it through to the service on submit |
| `modules/Http/Controllers/AnalyticsController.php` | Pass subscription data to the Analytics page |
| `modules/Http/Controllers/ProductController.php` | Enforce product limit on store() |
| `modules/Http/Requests/SubmitOnboardingRequest.php` | Add whatsapp_number validation |
| `resources/ts/pages/Vendor/Onboarding.tsx` | Add WhatsApp number field |
| `resources/ts/pages/Vendor/Analytics.tsx` | Add subscription status card |
| `config/services.php` | Add paystack.secret_key entry |
| `routes/web.php` | Add subscription routes |
| `routes/console.php` | Schedule ExpireVendorSubscriptions job |
| `database/seeders/DatabaseSeeder.php` | Register SubscriptionPlanSeeder |
