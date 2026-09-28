# Jiidaa — Testnet Payment Flow: Transaction Lifecycle & Attribution

**Instawards Deliverable 3 — Evidence & Documentation**
Network: **Stellar Testnet** · Asset: **NGNC** (Naira-pegged)

## 1. Overview

Vendors on Jiidaa pay for **coin packs** (coins are spent to receive buyer leads). Payment is
settled **on-chain on the Stellar testnet in NGNC**, producing a verifiable transaction hash for
every purchase. This documents the full lifecycle of one payment — from the vendor tapping "Buy"
to coins landing and the on-chain settlement — plus how each payment is attributed back to a
referrer and the vendor's activation journey.

## 2. End-to-end transaction lifecycle

```
Vendor                     Jiidaa backend                 Stellar Testnet            Result
  │                              │                              │                       │
  1. Taps "Buy pack"            │                              │                       │
  2. Confirms (name/email/      │                              │                       │
     pack/amount shown)         │                              │                       │
  ─────────────────────────────▶                              │                       │
                          3. Creates CoinPurchase (pending)    │                       │
                          4. Sends NGNC: distribution ─────────▶  on-chain payment     │
                             wallet → platform wallet        5. Settles, returns hash  │
                          6. Records AnchorTransaction  ◀───────  tx hash              │
                             (kind=coin_deposit, hash, amount)                          │
                          7. Credits coins + bonus to wallet                            │
                          8. Marks purchase completed ──────────────────────────────▶ 9. Vendor funded;
                                                                                          can receive
                                                                                          billed leads
```

**Step-by-step:**

1. **Initiate** — Vendor opens *Buy Coins* and selects a pack (e.g. Starter) or claims the
   one-time Free tier.
2. **Confirm** — A confirmation screen shows the payer (business name, email), the pack, and the
   amount. Vendor taps **Confirm & Pay**.
3. **Record** — Backend creates a `CoinPurchase` in `pending` state.
4. **Settle on-chain** — The platform's NGNC **distribution wallet** transfers the pack price to
   the **platform wallet** as a classic NGNC payment on Stellar testnet.
5. **Confirmation** — The network returns a **settled transaction hash**.
6. **Ledger** — Backend records an `AnchorTransaction` (`kind = coin_deposit`) storing the
   **tx hash**, amount, and completion time.
7. **Credit** — Coins (+ any bonus) are credited to the vendor's coin wallet.
8. **Complete** — The `CoinPurchase` is marked `completed`; a purchase receipt is issued.
9. **Outcome** — The vendor is now funded and eligible to receive **paid leads** (coins are
   debited per buyer contact).

## 3. On-chain proof (how to verify)

Every completed payment is independently verifiable:

- **Explorer:** `https://stellar.expert/explorer/testnet/tx/[TX_HASH]`
- **Platform (receiving) wallet:** `GAXAOGYEY6D5J2AJR2DCVCP6JZ4XZD5P5X56BKVMR2J27BL6WZH7R3RZ`
- **NGNC issuer:** `GDEKJ2IBFB6UHYYZ4ZFEZLGKVGSMNCYFEIXTOMDG57AG44EAGCBINAHA`
- In the **Admin → Payments** page, each coin payment links directly to its stellar.expert
  transaction.

## 4. Attribution metrics (referrer → activation → payment)

Each payment ties back to the growth data, so revenue is traceable to its source:

- **Referral attribution** — the referrer who brought the vendor (per-referrer tracking + leaderboard).
- **Activation funnel** — the vendor's journey: **signup → product listing → activation**.
- **Payment** — the vendor's coin purchase + on-chain tx hash.

**Sprint totals to report:**

- Total users: **324** · Fully testnet paid vendors: **50**
- Vendors with ≥1 completed testnet payment: **3**
- Total testnet payments settled: **63** · Total NGNC settled: **[sum]**

## 5. Why this is a foundation for the SCF Build Award

The payment rail is proven end-to-end on testnet with verifiable on-chain settlement, and every
transaction is attributable to a referrer and an activated vendor. The same flow migrates to
**mainnet** by swapping the testnet anchor/asset for a licensed NGN anchor — no change to the
lifecycle above.
