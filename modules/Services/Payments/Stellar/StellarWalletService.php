<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments\Stellar;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Models\StellarWallet;
use ModulesShoppingComplex\Models\User;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

final class StellarWalletService
{
    public function __construct(
        private readonly StellarSDK $sdk,
        private readonly Network $network,
        private readonly string $networkName,
        private readonly string $ngncAssetCode,
        private readonly string $ngncIssuer,
    ) {}

    /**
     * Return the vendor's wallet, provisioning one on first use.
     *
     * Idempotent: the unique `vendor_id` constraint collapses a concurrent double-create
     * back to the row that won.
     *
     * @throws \RuntimeException
     */
    public function getOrCreateForVendor(User $vendor): StellarWallet
    {
        $existing = StellarWallet::query()->where('vendor_id', $vendor->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        $keyPair = KeyPair::random();
        $publicKey = $keyPair->getAccountId();

        $this->fundAndTrust($keyPair, $publicKey);

        try {
            return StellarWallet::query()->create([
                'vendor_id' => $vendor->id,
                'public_key' => $publicKey,
                'encrypted_secret' => $keyPair->getSecretSeed(),
                'network' => $this->networkName,
            ]);
        } catch (UniqueConstraintViolationException) {
            return StellarWallet::query()->where('vendor_id', $vendor->id)->firstOrFail();
        }
    }

    /**
     * Fund the new account via friendbot and establish its NGNC trustline.
     *
     * @throws \RuntimeException
     */
    private function fundAndTrust(KeyPair $keyPair, string $publicKey): void
    {
        try {
            if (! FriendBot::fundTestAccount($publicKey)) {
                throw new \RuntimeException('Friendbot funding failed.');
            }

            $account = $this->sdk->requestAccount($publicKey);
            $changeTrust = (new ChangeTrustOperationBuilder(
                Asset::createNonNativeAsset($this->ngncAssetCode, $this->ngncIssuer),
            ))->build();

            $transaction = (new TransactionBuilder($account))->addOperation($changeTrust)->build();
            $transaction->sign($keyPair, $this->network);

            $this->sdk->submitTransaction($transaction);
        } catch (\Throwable $e) {
            Log::error('Stellar wallet provisioning failed', ['public_key' => $publicKey, 'error' => $e->getMessage()]);

            throw new \RuntimeException('Could not set up your Stellar wallet. Please try again.');
        }
    }
}
