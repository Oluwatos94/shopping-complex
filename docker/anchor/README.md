# Jiidaa SEP-24 Anchor (testnet)

Phase 2a of the Stellar integration — stands up our own [Stellar Anchor Platform](https://developers.stellar.org/docs/platforms/anchor-platform)
on **testnet**, running SEP-1 / SEP-10 / SEP-24 plus the stock Kotlin reference
business server, so a vendor can deposit **NGNC** through a hosted Naira deposit UI.

> Adapted from the upstream [`quick-run`](https://github.com/stellar/anchor-platform/tree/develop/quick-run)
> reference, trimmed to what we need and pinned to `stellar/anchor-platform:4.4.0`
> (the `quick-run` config schema is 4.x-era — older tags use an incompatible `assets.yaml`).
## What runs

| Service | Port | Role |
|---------|------|------|
| `platform` | 8080 (SEP), 8085 (Platform API) | SEP-1/10/24 endpoints + Stellar observer |
| `reference-server` | 8091 | Stock Kotlin business server — auto-confirms the simulated Naira deposit on testnet |
| `sep24-reference-ui` | 3000 | Hosted interactive deposit UI (the page we'll embed) |
| `kafka`, `db`, `reference-db` | — | Infra dependencies |

## Three testnet accounts (created + funded by `ap_start.sh`)

- **SEP-10 signing account** — signs SEP-10 auth challenges; its public key is `SIGNING_KEY` in `stellar.toml`.
- **Distribution account** — holds the NGNC float and signs the on-chain payout to the depositing vendor.
- **NGNC issuer** — issues the `NGNC` asset; the distribution account trusts it and receives a 10,000,000 NGNC float.

## Prerequisites

1. **Docker** running
2. **Stellar CLI** — https://github.com/stellar/stellar-cli. Verify: `stellar --version`.

## Bring it up

```bash
cd docker/anchor
./ap_start.sh
```

The script is idempotent: it reuses existing keypairs, (re)issues the NGNC float,
renders `config/*.yaml`+`.toml` from the `*.template` files, writes
`accounts.generated.env`, and runs `docker compose up -d`.

## Verify (Phase 2a exit check)

```bash
# 1. SEP-1 info file resolves and lists NGNC
curl -s http://localhost:8080/.well-known/stellar.toml | grep -A2 'code = "NGNC"'

# 2. SEP-24 advertises NGNC deposit
curl -s http://localhost:8080/sep24/info | jq '.deposit.NGNC'

# 3. SEP-10 challenge responds (replace G... with any funded testnet account)
curl -s "http://localhost:8080/auth?account=GBQ...XYZ" | jq '.transaction != null'

# 4. UI is up
curl -s -o /dev/null -w '%{http_code}\n' http://localhost:3000
```

All four green ⇒ the anchor is live. Next: Phase 2b (Laravel custodial wallet client).

## Documenting the 5 testnet deposits.

Once Phase 2b/2c drive deposits end-to-end, each completed SEP-24 transaction has a
`stellar_tx_hash`. View at `https://stellar.expert/explorer/testnet/tx/<hash>` and
record in `docs/stellar-deposits.md`.

## Files

| File | Tracked? | Purpose |
|------|----------|---------|
| `docker-compose.yaml` | ✅ | The stack |
| `dev.env` | ✅ | SEP toggles + testnet (dev-only) secrets |
| `config/*.template` | ✅ | Config with `${...}` placeholders |
| `ap_start.sh` | ✅ | Keypair + NGNC setup, render, bring-up |
| `config/*.yaml`, `config/*.toml` | ❌ (gitignored) | **Rendered — contains the distribution secret** |
| `accounts.generated.env` | ❌ (gitignored) | NGNC issuer + distribution public keys for the Laravel `.env` |

## Teardown

```bash
docker compose down        # stop; keeps testnet accounts (CLI-stored) for next run
docker compose down -v      # also wipe the postgres/kafka volumes
```
