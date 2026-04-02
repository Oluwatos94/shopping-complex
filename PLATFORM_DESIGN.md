# Shopping Complex — Platform Design Document

---

## 1. Vision

Shopping Complex is a GPS-powered vendor discovery platform. It connects buyers to the nearest vendors selling what they need, delivered through WhatsApp — the app buyers already use every day.

It is not a marketplace. Shopping Complex does not handle orders, payments, or delivery. It does one thing well: getting the right buyer in front of the right nearby vendor.

**Vendors** use the Shopping Complex web platform to manage their business listing and products.
**Buyers** use WhatsApp to search, browse, and connect with vendors near them.

The web platform is the engine. WhatsApp is the front door for buyers.

---

## 2. How It Works

### Buyer Flow (WhatsApp)

1. Buyer messages the Shopping Complex WhatsApp number
2. Bot asks what they are looking for
3. Buyer types their product or service need
4. Bot asks buyer to share their location
5. Buyer shares live location
6. Bot returns a list of nearby vendors selling that product (nearest first)
7. Buyer selects a vendor to view their product catalogue
8. Buyer views the vendor's listed products with prices
9. Buyer requests the vendor's contact
10. Bot sends the vendor's WhatsApp number
11. Buyer and vendor negotiate directly on WhatsApp — platform's job is done

### Vendor Flow (Web Platform)

1. Vendor registers and completes onboarding on the web platform
2. Admin reviews and approves the vendor
3. Vendor logs in and adds their products (name, price, photos, stock status)
4. Vendor sets their business location
5. Vendor chooses a subscription plan
6. Their products are now discoverable via the WhatsApp bot
7. Vendor sees analytics showing how many buyers found and viewed them

---

## 3. User Roles

**Buyer** — No account needed. Interacts entirely through WhatsApp. Never charged.

**Vendor** — Has an account on the web platform. Manages their product catalogue. Pays a subscription to stay discoverable. Receives buyer inquiries directly on their own WhatsApp number.

**Admin** — Approves vendor applications, moderates reviews, manages subscription plans, views platform analytics.

---

## 4. Platform Architecture

The platform has two distinct surfaces:

**Vendor Web App** (already largely built) — Laravel 11 + React + Inertia.js. Vendors log in here to manage everything.

**WhatsApp Bot** (new) — A webhook endpoint in the same Laravel app. Meta Cloud API sends incoming messages to this endpoint. The bot processes the message, queries the database, and sends a reply back through Meta's API.

Both surfaces share the same database. The bot reads vendor and product data that vendors have entered on the web platform.

---

## 5. Vendor Platform Features

### Registration & Onboarding
Vendors register and go through a KYC onboarding process before they can be listed. This already exists. One addition needed: a **WhatsApp number field**. This is the number buyers receive when they request contact — it can differ from the vendor's personal phone number.

### Product Management
Vendors add and manage their products: name, description, price, category, photos, and stock status. This already exists. The product limit per vendor is determined by their subscription plan.

### Vendor Dashboard
The vendor's home screen after login. Shows subscription status, a summary of discovery stats from WhatsApp (searches appeared in, catalogue views, contact requests), and quick links to add products or view full analytics.

### Subscription Management
Vendors choose and pay for a plan. The plan determines how many products they can list and how high they rank in search results. Payment is handled via Paystack or Flutterwave.

### Analytics
Vendors see metrics sourced from WhatsApp interactions: how many times they appeared in search results, how many buyers viewed their catalogue, how many buyers requested their contact, and which products were viewed most. This data is tracked per WhatsApp interaction event.

### Business Location
Vendors set their address with GPS coordinates during onboarding and can update it from their profile. This is what the bot uses to calculate distance from a buyer's shared location.

---

## 6. WhatsApp Bot Features

### Conversation State
The bot tracks where each buyer is in the conversation using a sessions table keyed by their phone number. States are: idle, awaiting location, showing vendor list, showing products. Sessions expire after 30 minutes of inactivity.

### Search
When a buyer sends a product/service name, the bot asks for their location. Once received, it queries the database for approved, active vendors within a configurable radius (default 5km) whose products match the search term. Results are ordered by distance first, then subscription tier (premium vendors rank above basic at the same distance), then rating.

### Vendor List
Returns up to 5 nearest matching vendors. Each entry shows: vendor name, distance, rating, and number of products. Buyer selects one by replying with its number.

### Product Catalogue
Returns the selected vendor's active products with names, prices, and stock status. Limited to 10 per message with pagination for larger catalogues.

### Contact Request
When the buyer replies with the contact command, the bot sends the vendor's WhatsApp number as a clickable wa.me link. The buyer then leaves Shopping Complex and chats the vendor directly.

### Supported Commands
Buyer can always type BACK to return to the vendor list, MENU to restart from the beginning, and HELP to see available commands.

---

## 7. Admin Dashboard Features

**Vendor Management** — View all vendors, review onboarding submissions, approve or reject with a reason, suspend active vendors (suspended vendors are hidden from bot search results).

**Subscription Management** — Create and edit plan tiers, set prices, view active subscriptions.

**Review Moderation** — Approve or reject pending reviews.

**Platform Analytics** — Total registered vendors, active subscribed vendors, total WhatsApp searches, total buyer-vendor connections made, monthly subscription revenue.

**Bot Monitoring** — View recent WhatsApp interactions to monitor bot health and identify unhandled messages.

---

## 8. Database Changes

### New Tables

**subscription_plans** — Stores available plan tiers (name, price, product limit, search priority, features list, active flag).

**vendor_subscriptions** — Tracks each vendor's current and past subscriptions (vendor, plan, status, start date, expiry date, payment reference, amount paid).

**whatsapp_sessions** — Maintains conversation state per buyer phone number (phone number, current state, session data as JSON, last active timestamp). Used to remember what step a buyer is on.

**whatsapp_interactions** — Logs every bot event for analytics (phone number, event type, search query, vendor involved, product involved, buyer's coordinates, timestamp). Event types: search, vendor_viewed, contact_requested, no_results.

### Additions to Existing Tables

**users** — Add `whatsapp_number` column. This is separate from `phone` so vendors can direct buyers to a dedicated business WhatsApp.

**profile_views and product_views** — Add `source` column (web or whatsapp) to distinguish how buyers are finding vendors.

### Tables to Deprioritize

**conversations and chat_messages** — The in-platform buyer-vendor chat is no longer needed. Buyers connect with vendors directly on WhatsApp. These tables can be repurposed for vendor-to-admin communication only, or removed entirely.

**customers_wishlist** — Buyers don't have platform accounts, so a wishlist is not relevant to the new model.

---

## 9. WhatsApp Integration

### What Is Needed to Set Up

1. A Meta Developer account and a WhatsApp Business phone number
2. A public HTTPS webhook URL pointing to the Laravel app
3. Three environment variables: Phone Number ID, Access Token, and a custom Verify Token
4. Two new routes: one GET for Meta's webhook verification, one POST for receiving messages
5. The POST handler must respond 200 immediately and process the message in a background job — Meta requires a fast response

### New Code Needed

**WhatsAppController** — Handles webhook verification and receives incoming message payloads.

**WhatsAppBotService** — The core bot logic. Reads the session state, determines what the buyer is asking, queries the appropriate service, formats the reply, and updates the session.

**WhatsAppApiService** — Sends messages back to buyers via the Meta Cloud API.

**ProcessWhatsAppWebhook job** — Queued job that runs the bot logic after the webhook responds 200.

**SendWhatsAppMessage job** — Queued job for sending outbound messages, preventing webhook timeouts.

**WhatsAppSession model** — Eloquent model for the sessions table.

**WhatsAppInteraction model** — Eloquent model for the interactions/analytics table.

### Modification to Existing Services

**VendorService::getNearbyVendors()** needs two additions: filter to only vendors with an active subscription (or the free plan), and accept a search query to match against product names and categories.

---

## 10. Revenue Model

### Subscription Tiers

| Plan | Price/month | Product Limit | Search Position |
|---|---|---|---|
| Free | NGN 0 | 10 products | Standard |
| Basic | NGN 5,000 | 30 products | Above Free |
| Premium | NGN 15,000 | Unlimited | Top of results |

Free tier exists to attract vendors and build supply early. All tiers are discoverable — paying vendors simply rank higher in results at the same distance.

### How Payment Works

Vendor selects a plan, pays via Paystack or Flutterwave, the payment callback activates their subscription record. An expired subscription removes the vendor from bot search results. A scheduled daily job checks for expired subscriptions and sends vendors a renewal reminder via WhatsApp.

### Platform's Revenue Sources (v1)

Vendor subscriptions only. No transaction fees, no buyer charges, no order involvement.

---

## 11. What Changes in the Codebase

### Keep Without Changes
Authentication, vendor onboarding, product management, category system, media uploads, review system, notifications, geolocation in Address model, admin dashboard base.

### Modify
Vendor onboarding form (add WhatsApp number field), VendorService getNearbyVendors (add subscription filter and search query), vendor analytics page (add WhatsApp metrics), admin dashboard (add subscription management and bot stats).

### Add
Four new database tables, two new service classes, one new controller, two new background jobs, two new Eloquent models, vendor subscription pages, admin subscription management pages.

### Remove or Deprioritize
In-platform buyer-vendor chat, customer wishlist, customer account registration (buyers do not need accounts).

---

## 12. Build Phases

### Phase 1 — Complete the Vendor Platform
Add WhatsApp number to onboarding. Build subscription plans and payment flow. Enforce subscription status on vendor visibility. Update vendor dashboard with subscription status.

### Phase 2 — WhatsApp Bot (MVP)
Set up Meta Developer account and configure the webhook. Build the bot state machine covering the full buyer flow: search → nearest vendors → product catalogue → contact handoff. Log all interactions to the whatsapp_interactions table.

### Phase 3 — Analytics
Surface WhatsApp interaction data on the vendor analytics dashboard and admin panel. Show per-vendor discovery stats and platform-wide bot usage.

### Phase 4 — Polish
Subscription expiry notifications sent to vendors via WhatsApp. Public web-accessible vendor profile pages (shareable links). Mobile optimisation of vendor dashboard. SEO for vendor profiles.
