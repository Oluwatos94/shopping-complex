# Stellar Testnet Deposit Evidence

**Deliverable 1 (SEP-24 Anchor Integration):** at least 5 successful testnet deposit
transactions, each verifiable on stellar.expert.

- **Network:** Stellar **Testnet**.
- **Asset:** NGNC (Naira-backed test token, issued by the self-hosted anchor).
- **Settlement destination:** Jiidaa platform wallet `GCSKOAZG…747`
  (`STELLAR_PLATFORM_WALLET_PUBLIC`). Each vendor deposit pays Jiidaa directly via the
  SEP-24 Anchor flow, which then activates the vendor's subscription.
- **Explorer prefix:** `https://stellar.expert/explorer/testnet/tx/<hash>`

> ⚠️ Stellar periodically resets testnet, which can wipe accounts and history. Capture and
> verify each link promptly. If a reset occurs, re-run `docker/anchor/ap_start.sh` and the
> Subscribe flow to regenerate fresh deposits.

## Documented deposits (4 / 5)

All settled on-chain on **2026-06-30** (verifiable on stellar.expert). Each deposit paid the exact
plan price and activated the vendor's subscription.

| # | Time (UTC) | Vendor | Plan | Amount | Status | Transaction hash | Explorer |
|---|-----------|--------|------|--------|--------|------------------|----------|
| 1 | 17:30:52 | homedecor@example.com | Premium (₦15,000) | 15000 NGNC | completed | `8e6bdef123acee5a0618dae514ba65e84d8b1fa7e9a57b20596d71cf600a8bac` | [view](https://stellar.expert/explorer/testnet/tx/8e6bdef123acee5a0618dae514ba65e84d8b1fa7e9a57b20596d71cf600a8bac) |
| 2 | 17:38:45 | fashion@example.com | Basic (₦5,000) | 5000 NGNC | completed | `f65a2e1e095950e9d0d5621f45182b605984cf195cfdffd4effa4bc705575e02` | [view](https://stellar.expert/explorer/testnet/tx/f65a2e1e095950e9d0d5621f45182b605984cf195cfdffd4effa4bc705575e02) |
| 3 | 17:42:15 | tech@example.com | Basic (₦5,000) | 5000 NGNC | completed | `98dbee4a7c623fc4effac41a81aab1ae1da0dfbbd2eb28c139fb84228ecf4b66` | [view](https://stellar.expert/explorer/testnet/tx/98dbee4a7c623fc4effac41a81aab1ae1da0dfbbd2eb28c139fb84228ecf4b66) |
| 4 | 17:45:50 | beauty@example.com | Premium (₦15,000) | 15000 NGNC | completed | `2ffaa8d42945872dc9b59db0bcceab0ae7545b6964e24dfb99d342d1e2da5428` | [view](https://stellar.expert/explorer/testnet/tx/2ffaa8d42945872dc9b59db0bcceab0ae7545b6964e24dfb99d342d1e2da5428) |
| 5 | _pending_ | | | | | | |

## How each deposit was produced

1. Start the anchor stack: `docker/anchor/ap_start.sh`, and run the Laravel app.
2. Log in as a vendor → **Subscription** → **Pay with: Direct transfer** →
   choose a paid plan → **Subscribe**.
3. Complete the Naira deposit in the SEP-24 deposit window.
4. The NGNC settles into the platform wallet on-chain and the subscription activates.

## Capturing the hash for a new deposit

```bash
php artisan tinker --execute='echo ModulesShoppingComplex\Models\AnchorTransaction::latest("id")->first()->stellar_tx_hash;'
```

Prefix the result with `https://stellar.expert/explorer/testnet/tx/` and add a row above.

## Week 1 — Setup status

Maps to the SOW Week 1 expected output: *"Testnet account funded, Laravel connected to
Stellar testnet, anchor partner identified with SEP-24 support confirmed."*

- **Stellar testnet setup — ✅ Complete.** The Laravel backend is connected to Stellar
  **testnet** (`STELLAR_NETWORK=testnet`, Horizon `https://horizon-testnet.stellar.org`). The
  platform wallet (`STELLAR_PLATFORM_WALLET_PUBLIC`) was created, funded via Friendbot, and given
  an NGNC trustline. It is the account each vendor deposit settles into.
- **SEP-24 anchor confirmed — ✅ Complete.** After evaluating external NGN anchors (which run on
  mainnet with live KYC/fiat and no open testnet), the official **Stellar Anchor Platform** (the
  SEP-10 + SEP-24 reference anchor) was self-hosted as the testnet anchor, issuing a Naira test
  token (**NGNC**). It runs from `docker/anchor/` (`docker-compose.yaml` + `ap_start.sh`) and
  serves SEP-10 auth and the SEP-24 interactive deposit endpoints under `STELLAR_ANCHOR_BASE_URL`.
  Selecting a licensed production NGN anchor is a mainnet task, listed as out of scope for this
  Instaward.
- **API credentials obtained — ✅ Complete.** The anchor's SEP-10 signing key, NGNC issuer, and
  distribution/platform account keys are generated into `docker/anchor/accounts.generated.env` and
  wired into the app via `config/services.php` (`stellar.*`): `anchor_sep10_signing_key`,
  `ngnc_issuer`, `distribution_public`, `platform_wallet_public/secret`.
- **Hosted deposit UI scoped — ✅ Complete.** The SEP-24 interactive flow renders the anchor's
  hosted deposit page in an embedded iframe on the vendor subscription page, with the app polling
  the deposit's status and activating the subscription on completion — implemented in
  `StellarDepositModal.tsx` and the `stellarStatus` controller endpoint.
