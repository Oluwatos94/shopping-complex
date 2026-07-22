<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments\Stellar;

use ModulesShoppingComplex\Billing\Models\StellarWallet;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

/**
 * Seeds a vendor's custodial wallet with test NGNC from the platform wallet so the
 * scheduled MPP charge has a balance to pull. Testnet-only: on mainnet the vendor would
 * fund their own wallet via a SEP-24 deposit (deferred). A classic NGNC payment is used
 * (not the SAC), so this works even before STELLAR_NGNC_SAC is configured.
 */
final readonly class StellarTestnetFunder
{
    public function __construct(
        private StellarSDK $sdk,
        private Network $network,
        private StellarSigner $platformSigner,
        private string $ngncAssetCode,
        private string $ngncIssuer,
    ) {}

    /**
     * Send $amount NGNC from the platform wallet to the vendor's custodial wallet.
     *
     * @throws \Throwable when the payment cannot be submitted (the caller decides whether to swallow)
     */
    public function fund(StellarWallet $wallet, float $amount): void
    {
        $source = KeyPair::fromSeed($this->platformSigner->secret);
        $account = $this->sdk->requestAccount($this->platformSigner->publicKey);

        $payment = (new PaymentOperationBuilder(
            $wallet->public_key,
            Asset::createNonNativeAsset($this->ngncAssetCode, $this->ngncIssuer),
            $this->formatAmount($amount),
        ))->build();

        $transaction = (new TransactionBuilder($account))->addOperation($payment)->build();
        $transaction->sign($source, $this->network);

        $this->sdk->submitTransaction($transaction);
    }

    /** The wallet's current NGNC balance (0 when the account or trustline doesn't exist yet). */
    public function ngncBalance(StellarWallet $wallet): float
    {
        try {
            $account = $this->sdk->requestAccount($wallet->public_key);
        } catch (\Throwable) {
            return 0.0;
        }

        foreach ($account->getBalances() as $balance) {
            if ($balance->getAssetCode() === $this->ngncAssetCode
                && $balance->getAssetIssuer() === $this->ngncIssuer) {
                return (float) $balance->getBalance();
            }
        }

        return 0.0;
    }

    /** Classic payment amounts are decimal strings; NGNC displays 2 dp per the anchor's asset config. */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
