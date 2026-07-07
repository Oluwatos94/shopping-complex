#!/bin/bash
#
# Jiidaa SEP-24 anchor bring-up.
#
# Extends the upstream anchor-platform/quick-run/ap_start.sh with the NGNC asset:
# besides the SEP-10 host account and the distribution account, it creates an NGNC
# issuer, adds a distribution -> issuer trustline, and issues an NGNC float to the
# distribution wallet so SEP-24 deposits have something to pay out.
#
# Idempotent: re-running reuses existing keypairs (stored by the Stellar CLI) and
# re-renders the config from templates.

set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

echo -e "${GREEN}Starting Jiidaa SEP-24 anchor setup...${NC}"

# --- prerequisites ----------------------------------------------------------
if command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
elif docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    echo -e "${RED}Error: docker compose is not available. On WSL, enable Docker Desktop WSL integration.${NC}"
    exit 1
fi

if ! command -v stellar &> /dev/null; then
    echo -e "${RED}Error: Stellar CLI not installed. See https://github.com/stellar/stellar-cli${NC}"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# ---------------------------------------------------------------------------
# Generate (or reuse) and fund a testnet keypair, echoing nothing.
#   $1 = Stellar CLI identity name
# ---------------------------------------------------------------------------
ensure_account() {
    local name="$1"
    if stellar keys secret "$name" &>/dev/null; then
        echo "  ✓ Reusing keypair: $name" >&2
    else
        echo "  ℹ Generating + funding: $name" >&2
        stellar keys generate "$name" --fund --network testnet >&2 \
            || stellar keys generate "$name" --fund >&2
    fi
}

echo -e "${YELLOW}Step 1: Host SEP-10, distribution, NGNC issuer, and platform accounts${NC}"
ensure_account "ap-sep10-account"
ensure_account "ap-distribution-account"
ensure_account "ap-ngnc-issuer"
# Jiidaa's revenue wallet — receives Anchor (SEP-24) subscription payments.
ensure_account "ap-platform-wallet"

HOST_SEP10_SECRET_KEY=$(stellar keys secret     "ap-sep10-account"        | head -1)
HOST_SEP10_ACCOUNT=$(stellar keys public-key    "ap-sep10-account"        | head -1)
DISTRIBUTION_ACCOUNT=$(stellar keys public-key  "ap-distribution-account" | head -1)
DISTRIBUTION_ACCOUNT_SECRET_KEY=$(stellar keys secret "ap-distribution-account" | head -1)
NGNC_ISSUER=$(stellar keys public-key           "ap-ngnc-issuer"          | head -1)
PLATFORM_WALLET_ACCOUNT=$(stellar keys public-key "ap-platform-wallet"    | head -1)
PLATFORM_WALLET_SECRET=$(stellar keys secret    "ap-platform-wallet"      | head -1)

for v in HOST_SEP10_SECRET_KEY HOST_SEP10_ACCOUNT DISTRIBUTION_ACCOUNT DISTRIBUTION_ACCOUNT_SECRET_KEY NGNC_ISSUER PLATFORM_WALLET_ACCOUNT PLATFORM_WALLET_SECRET; do
    if [ -z "${!v}" ]; then echo -e "${RED}Error: $v is empty${NC}"; exit 1; fi
done

echo -e "${GREEN}  SEP-10 account: ${NC}$HOST_SEP10_ACCOUNT"
echo -e "${GREEN}  Distribution:   ${NC}$DISTRIBUTION_ACCOUNT"
echo -e "${GREEN}  NGNC issuer:    ${NC}$NGNC_ISSUER"
echo -e "${GREEN}  Platform wallet:${NC}$PLATFORM_WALLET_ACCOUNT"

# --- NGNC trustline + float --------------------------------------------------
# significant_decimals is 2 for display, but on-chain amounts are always 7dp /
# stroops. Issue a 10,000,000 NGNC float = 10_000_000 * 10^7 stroops.
NGNC_FLOAT_STROOPS="100000000000000"

echo -e "${YELLOW}Step 2: NGNC trustlines (distribution + platform wallet -> issuer)${NC}"
for acct in ap-distribution-account ap-platform-wallet; do
    if stellar tx new change-trust \
            --source-account "$acct" \
            --line "NGNC:$NGNC_ISSUER" \
            --network testnet >/dev/null 2>&1; then
        echo "  ✓ Trustline set for $acct (or already present)"
    else
        echo "  ℹ change-trust for $acct returned non-zero (likely already exists); continuing"
    fi
done

echo -e "${YELLOW}Step 3: Issue NGNC float (issuer -> distribution)${NC}"
if stellar tx new payment \
        --source-account ap-ngnc-issuer \
        --destination "$DISTRIBUTION_ACCOUNT" \
        --asset "NGNC:$NGNC_ISSUER" \
        --amount "$NGNC_FLOAT_STROOPS" \
        --network testnet >/dev/null 2>&1; then
    echo "  ✓ Issued NGNC float to distribution wallet"
else
    echo -e "  ${YELLOW}ℹ Payment returned non-zero — distribution may already hold NGNC; verify on stellar.expert${NC}"
fi

# --- NGNC Stellar Asset Contract (SAC) --------------------------------------

echo -e "${YELLOW}Step 3b: Deploy NGNC SAC (Soroban Asset Contract)${NC}"
NGNC_SAC=$(stellar contract id asset --asset "NGNC:$NGNC_ISSUER" --network testnet 2>/dev/null | head -1)
if stellar contract asset deploy --asset "NGNC:$NGNC_ISSUER" \
        --source-account ap-distribution-account --network testnet >/dev/null 2>&1; then
    echo "  ✓ NGNC SAC deployed: $NGNC_SAC"
else
    echo "  ℹ NGNC SAC already deployed (or deploy skipped): $NGNC_SAC"
fi

# --- render config from templates -------------------------------------------
echo -e "${YELLOW}Step 4: Render config from templates${NC}"
render() { # render <template> <output>
    sed -e "s|\${DISTRIBUTION_ACCOUNT_SECRET_KEY}|$DISTRIBUTION_ACCOUNT_SECRET_KEY|g" \
        -e "s|\${DISTRIBUTION_ACCOUNT}|$DISTRIBUTION_ACCOUNT|g" \
        -e "s|\${HOST_SEP10_ACCOUNT}|$HOST_SEP10_ACCOUNT|g" \
        -e "s|\${NGNC_ISSUER}|$NGNC_ISSUER|g" \
        "$1" > "$2"
    echo "  ✓ $2"
}
render config/reference-config.yaml.template   config/reference-config.yaml
render config/stellar.localhost.toml.template  config/stellar.localhost.toml
render config/assets.yaml.template             config/assets.yaml

# Persist the generated accounts for the Laravel .env. Gitignored — contains the platform
# wallet's testnet secret (Jiidaa's own custodial key, needed to authenticate SEP-10).
cat > accounts.generated.env <<EOF
# Generated by ap_start.sh — testnet values for the Laravel .env. Gitignored (holds a secret).
STELLAR_NGNC_ASSET_CODE=NGNC
STELLAR_NGNC_ISSUER=$NGNC_ISSUER
STELLAR_NGNC_SAC=$NGNC_SAC
STELLAR_PLATFORM_DISTRIBUTION_PUBLIC=$DISTRIBUTION_ACCOUNT
STELLAR_ANCHOR_SEP10_SIGNING=$HOST_SEP10_ACCOUNT
STELLAR_PLATFORM_WALLET_PUBLIC=$PLATFORM_WALLET_ACCOUNT
STELLAR_PLATFORM_WALLET_SECRET=$PLATFORM_WALLET_SECRET
EOF
echo "  ✓ accounts.generated.env (issuer/distribution/platform wallet for Laravel)"

# --- bring up ----------------------------------------------------------------
echo -e "${YELLOW}Step 5: docker compose up${NC}"
HOST_SEP10_SECRET_KEY="$HOST_SEP10_SECRET_KEY" $DOCKER_COMPOSE up -d

cat <<EOF

${GREEN}========================================${NC}
${GREEN}Jiidaa SEP-24 anchor is starting!${NC}
${GREEN}========================================${NC}

Accounts:
  SEP-10 signing : $HOST_SEP10_ACCOUNT
  Distribution   : $DISTRIBUTION_ACCOUNT
  NGNC issuer    : $NGNC_ISSUER

Endpoints:
  SEP server     : http://localhost:8080
  stellar.toml   : http://localhost:8080/.well-known/stellar.toml
  SEP-24 /info   : http://localhost:8080/sep24/info
  Platform API   : http://localhost:8085
  Reference srv  : http://localhost:8091
  SEP-24 UI      : http://localhost:3000

Logs : $DOCKER_COMPOSE logs -f
Stop : $DOCKER_COMPOSE down
EOF
