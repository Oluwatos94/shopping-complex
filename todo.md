# Stellar Integration — Implementation Plan (Instaward Grant)

Integrate Stellar (SEP-24 anchor + recurring on-chain settlement) into Jiidaa's vendor
subscription flow, **without throwing away the existing Paystack flow** — both become
swappable payment providers behind one interface.

> Network: **Stellar Testnet** only. Mainnet, full dashboard, audit, OSS publication are out of scope.

---

## 0. Guiding principles

- **Switchable payment methods.** Today `SubscriptionService` depends directly on
  `PaystackClient`. We refactor so it depends on a `PaymentProvider` contract. Paystack and
  Stellar become two interchangeable implementations selected at runtime by a
  `PaymentMethodEnum`. Adding a third provider later = one new class + one enum case.
- **Don't disturb the working Paystack path.** Wrap it, don't rewrite it. All existing tests
  must stay green.
- **Vendor UX stays "pay Naira as normal."** The Stellar rails (NGNC mint, on-chain
  settlement) run behind the scenes. The vendor sees a Naira deposit UI, not crypto jargon.
- **Custodial wallets** (per the grant model): the platform creates & holds each vendor's
  testnet keypair so the MPP recurring server can settle each cycle without the vendor
  re-signing. Vendor gives **one-time consent** at subscribe.
- **Settlement model:** SEP-24 deposit funds the vendor's NGNC balance → each billing cycle a
  single **MPP charge** (Soroban SAC `transfer_from`, vendor → platform) settles that month on-chain.
  No channels, no upfront lock-up; every month is its own on-chain record. See Phase 3.
- **MPP = Merchant Payment Protocol** (Stellar agentic payments, Soroban-based, `@stellar/mpp`
  JS/TS SDK). Because the SDK is JS, the MPP server runs as a **Bun sidecar** (matches the repo
  `CLAUDE.md` Bun preference); Laravel calls it over internal HTTP.
  > **Charge mode, not Channel mode** (confirmed with Stellar docs AI): channel mode is for
  > high-frequency micro-payments and forces upfront capital lock-up with no per-payment trail.
  > For monthly billing we use **Charge + pull credentials** — see Phase 3.

---

## Architecture: where the pieces live

| Role | What it is | Who owns it |
|------|------------|-------------|
| **Platform server** | Anchor Platform Docker image — runs SEP-1 (`stellar.toml`), SEP-10 auth, SEP-24 API. Watches the Stellar network. | Stellar-provided, we configure |
| **Business server** | Our custom callbacks: fees, rates, KYC, deposit processing; drives tx state via JSON-RPC (`request_offchain_funds`, `notify_offchain_funds_received`, `notify_onchain_funds_sent`). Hosts the interactive deposit UI. | **Us** (can start from the reference server) |
| **Wallet client** | Jiidaa's Laravel app: SEP-10 auth with the vendor's custodial keypair → `POST /transactions/deposit/interactive` → embed URL → poll status. | **Us** (Laravel) |

---

## Phase 0 — Spike: settle the asset model before building (½–1 day)

> **Why first:** SEP-24 (Phase 2) mints money in the **classic asset** format; an MPP charge
> (Phase 3) is a **Soroban SAC `transfer_from`**, so it needs the asset as a **SAC**. The spike
> is now a *validation*, not an investigation — the docs already resolved the asset question
> (see below). **No Laravel code yet.**
>
> **RESOLVED — one shared NGNC token, no wrap/unwrap (docs-confirmed + validated on testnet):**
> Deploying `NGNC:G...` as a SAC does **not** create a new token — the SAC is just an API over
> the *same* asset. A SAC `transfer` debits the exact classic trustline balance the anchor
> minted. **No conversion, no bridge.** → Decision (a).
> **Spike proof:** `services/mpp-spike/src/01-asset-validation.ts` issued classic NGNC
> issuer→vendor (1000), deployed the SAC, did a SAC `transfer` vendor→platform (150); the
> vendor's **classic trustline** dropped by exactly 150. Tx `9421fa3f…` on stellar.expert.

- [ ] Scratch `services/mpp-spike/` Bun script using `@stellar/mpp` against **testnet**.
- [ ] Create + friendbot-fund a couple of testnet keypairs (a "vendor" and the "platform").
- [x] Deploy the **classic NGNC → SAC** and **validate the resolved decision on-chain**: a SAC
      `transfer` debits the same trustline balance the anchor minted (no separate token). ✅ done.
- [x] **Mechanism finding (from `@stellar/mpp` 0.6.0 source):** charge does a plain SEP-41
      **`transfer` signed by the payer** — there is **no `approve`/`allowance`/`transfer_from`**
      in the SDK. "Pull mode" just means *the server broadcasts the client-signed tx*; "push
      mode" means the client broadcasts. ⇒ For unattended recurring, the **platform (custodial)
      re-signs the transfer each cycle** with the held vendor key; "authorise once" is a
      business-layer consent, not an on-chain allowance. (Corrects the earlier ERC-20 framing.)
- [x] Run the MPP **charge** flow end-to-end via the SDK (`services/mpp-spike/src/02-mpp-charge.ts`):
      402 challenge → client-signed SEP-41 `transfer` → **server-broadcast (pull)** on the NGNC
      SAC → settled (vendor 1000→950, platform 0→50). ✅ done. Note: in pull mode the tx hash is
      server-side (client `onProgress` ends at `signed`); the **sidecar must read the hash from
      the server receipt** for the evidence trail.
- [ ] Check **sponsored fees** — the charge `feePayer` option (platform covers the vendor's
      network fee; server rebuilds + signs the envelope). *(not yet exercised in the spike)*
- [ ] Note the **issuer-flag gotchas** to design for: `AUTH_REQUIRED`/`AUTH_REVOCABLE` (vendor
      needs an authorized trustline before SAC credit), frozen/revoked account → transfer fails,
      clawback differs for contract vs trustline balances.
- [ ] **Record in this file:** the MPP sidecar API surface (`/charge`, `/status`) and how the
      consumed-tx/challenge replay-protection `Store` persists (Redis/Postgres).

**Exit:** Decision (a) validated on testnet ✅, and the SDK charge flow settles end-to-end.
Phases 2 & 3 build on a settled, single-token, custodial-signing foundation.

---

## Phase 1 — Pluggable payment-provider abstraction (the "switch")

Refactor first so Stellar slots in cleanly. No behaviour change for vendors yet.

- [ ] Add `modules/Models/Enums/PaymentMethodEnum.php` → `PAYSTACK`, `STELLAR`.
- [ ] Define contract `modules/Services/Payments/Contracts/PaymentProvider.php`:
  ```php
  interface PaymentProvider {
      public function method(): PaymentMethodEnum;
      public function supportsRecurring(): bool;
      // Returns a CheckoutSession: { type: 'redirect'|'interactive', url, reference }
      public function startCheckout(User $vendor, SubscriptionPlan $plan): CheckoutSession;
      // Verify a completed payment; returns PaymentResult { success, amountPaid, reference }
      public function confirm(string $reference, User $vendor): PaymentResult;
  }
  ```
- [ ] Add value objects `CheckoutSession` and `PaymentResult` (readonly DTOs) under
      `modules/Services/Payments/`.
- [ ] Implement `PaystackProvider` — wraps the existing `PaystackClient`; move the
      kobo-conversion + `initiatePayment`/`handlePaystackCallback` logic out of
      `SubscriptionService` into here. `type: 'redirect'`, `supportsRecurring(): false`.
- [ ] Add `PaymentProviderManager` (resolver): `for(PaymentMethodEnum): PaymentProvider`.
      Bind it + the two providers in `app/Providers/AppServiceProvider.php`.
- [ ] Refactor `SubscriptionService`:
  - `initiatePayment(vendor, plan, PaymentMethodEnum)` → delegates to resolved provider.
  - `handleCallback(method, reference, vendorId)` → provider-agnostic; keeps the existing
    idempotency + race-safety logic (`findByPaymentReference`, `DB::transaction`,
    `UniqueConstraintViolationException` recovery) **unchanged**.
- [ ] Migration: add `payment_method` (enum, default `paystack`) to `vendor_subscriptions`.
      Backfill existing rows to `paystack`. Add cast on `VendorSubscription`.
- [ ] `SubscriptionController`: accept a `method` param on checkout; default `paystack`.
      Callback route stays for Paystack; add a Stellar status/confirm route in Phase 2.
- [ ] Frontend `resources/ts/pages/Vendor/Subscription/Index.tsx`: add a payment-method
      selector (Paystack card vs Stellar/Naira-on-chain) on the Subscribe action.
- [ ] Tests: existing Paystack flow passes through the new abstraction unchanged.

**Exit:** Paystack works exactly as before, but now via `PaymentProvider`. Ready to add Stellar.

---

## Phase 2 — Deliverable 1: SEP-24 Anchor Integration (Testnet on-ramp)

> **Goal:** ≥5 documented testnet deposit transactions on stellar.expert, with a hosted
> deposit UI embedded in the subscription page.

### 2a. Stand up the Anchor Platform (own anchor)
- [ ] Add `docker/anchor/` with Anchor Platform `docker-compose.yaml` (Platform server +
      Postgres + reference business server) wired to Stellar **testnet**.
- [ ] `config/` for the anchor: `assets.yaml` (define test **NGNC** asset + issuer),
      `stellar.<host>.toml` (SEP-1), `clients.yaml`, `dev.env` (SEP-10 signing seed, JWT
      secrets, platform/business API auth).
- [ ] Create & fund (friendbot) the anchor **issuer** and **distribution** accounts on testnet.
- [ ] Bring it up; verify `stellar.toml`, SEP-10 challenge, and SEP-24 `/info` respond.
- [ ] Decide business-server strategy: start with the **reference server** (auto-confirms the
      simulated Naira deposit on testnet); customise callbacks only as needed.

### 2b. Laravel as custodial wallet client
- [ ] Add a Stellar PHP SDK (e.g. `soneso/stellar-php-sdk`) via composer.
- [ ] `StellarWallet` model + migration: `vendor_id`, `public_key`, `encrypted_secret`
      (Laravel `Crypt`), `network`. One custodial keypair per vendor, created lazily on first
      Stellar checkout; friendbot-fund + add NGNC trustline on testnet.
- [ ] `modules/Services/Payments/Stellar/AnchorClient.php` — thin client:
  - SEP-10: fetch challenge, sign with vendor keypair, exchange for JWT.
  - SEP-24: `POST /transactions/deposit/interactive` → returns interactive `url` + `id`.
  - `GET /transaction?id=` → poll status.
- [ ] `AnchorTransaction` model + migration: `vendor_id`, `sep24_id`, `kind` (deposit),
      `status`, `amount`, `asset`, `stellar_tx_hash`, `started_at`, `completed_at`.
      (This table is the evidence trail for the 5 documented deposits.)

### 2c. StellarProvider + UI
- [ ] `StellarProvider implements PaymentProvider`: `startCheckout` →
      `type: 'interactive'`, `url` = anchor interactive URL; persists an `AnchorTransaction`.
      `supportsRecurring(): true`.
- [ ] `confirm()` checks the SEP-24 transaction reached `completed` and the NGNC landed in the
      vendor wallet, then triggers subscription settlement (Phase 3 payment) → activates sub.
- [ ] Subscription page: embed the interactive deposit URL (iframe/popup) when method =
      Stellar; poll a new `vendor.subscription.stellar.status` route until `completed`/`error`,
      then refresh the page state.
- [ ] **Document ≥5 testnet deposits**: run 5 end-to-end deposits, capture each
      `stellar_tx_hash`, link on `stellar.expert/explorer/testnet/tx/<hash>`. Save to
      `docs/stellar-deposits.md` for the grant submission.

**Exit:** A vendor can fund their NGNC wallet through the embedded Naira deposit UI; 5 deposits
are on-chain and documented.

---

## Phase 3 — Deliverable 2: MPP Subscription Server (recurring auto-renew)

> **Goal:** Vendor authorises once; subscription auto-renews each billing cycle via a scheduled
> MPP **charge**; access auto-revoked on lapse.
>
> **MPP** = Merchant Payment Protocol (Soroban-based agentic payments). Roles here:
> merchant/recipient = **Jiidaa platform**; customer/funder = **vendor**. The charge asset is a
> **Soroban Asset Contract (SAC)** — the NGNC the anchor mints, used via its wrapped SAC.
>
> **Mode choice — Charge, not Channel** (docs AI + confirmed against `@stellar/mpp` 0.6.0
> source). Channel mode is for high-frequency micro-payments and forces upfront capital lock-up
> with no per-payment on-chain record. Monthly billing is low-frequency, so use **Charge**:
> - *Charge* = one on-chain SEP-41 SAC **`transfer`** per cycle (vendor → platform). Clean
>   monthly record, no lock-up.
> - **How "authorise once" actually works (corrected):** the SDK has **no `approve`/allowance/
>   `transfer_from`**. Each charge is a fresh `transfer` *signed by the payer*. Because we are
>   **custodial**, the platform holds the vendor key and **re-signs the monthly `transfer`** with
>   no vendor present — the vendor's one-time on-screen consent is a *business-layer*
>   authorisation, not an on-chain allowance. "Pull mode" = the **server broadcasts** the
>   platform-signed tx; "push mode" = it broadcasts itself. MPP charge also supports
>   **`feePayer`** so the platform can sponsor the vendor's network fee.
> - ⚠️ **Constraint this creates:** unattended recurring works *only* custodially (only the
>   keyholder can authorise each charge). Non-custodial recurring would need a different
>   primitive (a recurring/allowance Soroban contract) — out of scope here.

### 3a. MPP server (Bun sidecar)
- [ ] New `services/mpp/` Bun service using `@stellar/mpp` charge (server). Internal HTTP API to
      Laravel: `POST /charge` (sign + broadcast this cycle's SEP-41 `transfer` vendor → platform),
      `GET /status/:ref` (tx result). Run via `bun --hot ./services/mpp/index.ts`.
- [ ] Back the charge `Store` (challenge + consumed-tx-hash replay protection) with the shared DB
      or Redis — not in-memory — so it survives restarts and is shared across instances.
- [ ] Config: the NGNC **SAC** id, platform recipient account, and (optional) `feePayer` account.

### 3b. Authorise once (business-layer consent)
- [ ] At first Stellar subscribe: after the SEP-24 deposit funds the vendor's NGNC balance,
      capture the vendor's **one-time consent** to recurring billing (cap, monthly cadence,
      end/expiry date). No on-chain `approve` — the platform's custody of the key is what lets it
      sign each cycle; this record is the policy the renewal job enforces.
- [ ] `SubscriptionAuthorization` model + migration: `vendor_id`, `plan_id`, `monthly_cap`,
      `valid_until`, `status`, `consent_at`.
- [ ] Handle **expiry/refresh**: prompt the vendor to re-consent before `valid_until`; the
      renewal job validates each charge against `monthly_cap` + `valid_until` before signing.

### 3c. Recurring renewal (scheduled charge)
- [ ] `RenewVendorSubscriptions` job (mirror `ExpireVendorSubscriptions`):
  - Find Stellar subs with valid, unexpired consent, due to renew.
  - Call sidecar `POST /charge` (platform-signed SEP-41 `transfer` vendor → platform) for the
    cycle price.
  - On success → extend `expires_at` +1 month, record `stellar_tx_hash` (`payment_reference`)
    + `AnchorTransaction` (kind = `mpp_charge`), notify.
  - On insufficient balance / expired consent / failure → do **not** extend → lapse.
- [ ] Lapse = revoke: confirm Stellar subs flow through the existing `ExpireVendorSubscriptions`
      (scheduled daily 00:00 in `routes/console.php`), which batch-expires + notifies. Schedule
      `RenewVendorSubscriptions` just before it.
- [ ] Top-up path: when the vendor's NGNC balance is low, route them back through a SEP-24
      deposit (Phase 2).
- [ ] Idempotency: one charge per (vendor, billing cycle) — unique key on the charged-cycles
      ledger, reusing the existing unique-constraint recovery pattern.
- [ ] Tests: charge succeeds → renew extends; low balance / expired auth → lapse + revoke;
      idempotent re-run charges only once.

**Exit:** Vendor consents once; each cycle the platform (custodial) signs an MPP charge that
settles that month on-chain; failed charge or expired consent revokes access.

---

## Phase 4 — Deliverable 3 (optional): WhatsApp payment notifications

> Reuse existing `WhatsAppApiService::sendText()` + `NotificationService`.

- [ ] On Stellar payment **confirmed** (deposit completed / renewal settled) and **failed**
      (renewal lapse), notify the vendor's `whatsapp_number` via `WhatsAppApiService::sendText`.
- [ ] Hook from the settlement/renewal services (or via a `SubscriptionPaymentEvent` listener)
      so it's decoupled. Respect `NotificationPreference` if applicable.
- [ ] Templates: "✅ Your Jiidaa subscription payment of ₦X is confirmed…" /
      "⚠️ Your subscription renewal failed — top up to keep your store active."
- [ ] Test with a real testnet payment event end-to-end.

---

## Testing & verification
- [ ] `bun test` / `php artisan test` — existing Paystack suite stays green after Phase 1.
- [ ] Unit tests per provider (`PaystackProvider`, `StellarProvider`) against the contract.
- [ ] Integration: full Stellar deposit → settle → activate against the local anchor.
- [ ] Manual: 5 documented testnet deposits on stellar.expert (grant evidence).

## Config / secrets to add (`.env.example` + `config/services.php`)
- [ ] `STELLAR_NETWORK=testnet`, `STELLAR_HORIZON_URL`, `STELLAR_ANCHOR_BASE_URL`
- [ ] `STELLAR_NGNC_ASSET_CODE`, `STELLAR_NGNC_ISSUER`
- [ ] `STELLAR_PLATFORM_DISTRIBUTION_PUBLIC`, `STELLAR_PLATFORM_DISTRIBUTION_SECRET`
- [ ] `STELLAR_WALLET_ENCRYPTION` (uses app key via `Crypt`)
- [ ] `STELLAR_SOROBAN_RPC_URL`, `STELLAR_NGNC_SAC` (Soroban Asset Contract id for MPP charges)
- [ ] `MPP_PLATFORM_RECIPIENT` (charge destination), optional `MPP_FEE_SPONSOR` account
- [ ] `MPP_SERVICE_URL` (internal URL of the Bun MPP sidecar) + shared auth secret
- [ ] `config/services.php` → `stellar` + `mpp` blocks (mirror the existing `paystack` block)

## Out of scope (per task)
Mainnet deployment, full vendor dashboard UI, OSS publication, security audit.

---

## Open decisions / spikes
- [x] **MPP mode** — **Charge + pull credentials** (per Stellar docs AI). Channel mode rejected:
      built for high-frequency micro-payments, forces upfront lock-up, no per-payment trail.
- [ ] **Bun sidecar vs PHP** — run the official `@stellar/mpp` JS SDK in a Bun service
      (recommended) vs. reimplement the charge/auth flow in `soneso/stellar-php-sdk` (heavy).
- [x] **Asset** — **Resolved (docs-confirmed): one shared NGNC token.** The classic NGNC the
      anchor mints is charged via its wrapped **SAC** (same balance, no separate token, no
      wrap/unwrap step). Phase 0 only *validates* this on testnet.
- [x] **Recurring mechanism** — **Resolved (SDK source):** no on-chain allowance; custodial
      platform re-signs a SEP-41 `transfer` each cycle. Consent is business-layer + key custody.
- [ ] **Consent expiry UX** — how long a consent stays valid and the vendor re-consent prompt.
- [ ] **Issuer flags** — decide NGNC issuer flags; `AUTH_REQUIRED`/`AUTH_REVOCABLE` mean vendors
      need an authorized trustline before SAC credit; frozen/revoked accounts fail charges.
- [ ] Custodial key storage hardening for testnet (DB `Crypt` now; KMS is a mainnet follow-on).
- [ ] How much of the reference business server to customise vs. run as-is for the PoC.
