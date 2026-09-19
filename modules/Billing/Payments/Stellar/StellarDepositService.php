<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments\Stellar;

use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Enums\AnchorReconciliationEnum;
use ModulesShoppingComplex\Billing\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Billing\Enums\Sep24StatusEnum;
use ModulesShoppingComplex\Billing\Models\AnchorTransaction;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Payments\CheckoutSession;
use ModulesShoppingComplex\Billing\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Identity\Models\User;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

class StellarDepositService
{
    public function __construct(
        private readonly AnchorClient $anchor,
        private readonly StellarSigner $platformSigner,
        private readonly StellarSDK $sdk,
        private readonly Network $network,
        private readonly StellarSigner $distributionSigner,
        private readonly string $ngncAssetCode,
        private readonly string $ngncIssuer,
        private readonly string $platformWalletPublic,
    ) {}

    /**
     * Open an NGNC deposit for $plan that settles into Jiidaa's platform wallet, and record it.
     *
     * @throws \RuntimeException
     */
    public function start(User $vendor, SubscriptionPlan $plan): CheckoutSession
    {
        $jwt = $this->anchor->authenticate($this->platformSigner);
        $interactive = $this->anchor->startNgncDeposit($this->platformSigner, $jwt, (float) $plan->price);

        AnchorTransaction::query()->create([
            'vendor_id' => $vendor->id,
            'plan_id' => $plan->id,
            'sep24_id' => $interactive->id,
            'kind' => AnchorTransactionKindEnum::DEPOSIT,
            'status' => Sep24StatusEnum::INCOMPLETE->value,
            'amount' => $plan->price,
            'asset' => $this->ngncAssetCode,
            'started_at' => now(),
        ]);

        return new CheckoutSession(CheckoutTypeEnum::INTERACTIVE, $interactive->url, $interactive->id);
    }

    public function settleNgncToPlatform(float $amount, string $memo): string
    {
        try {
            $asset = Asset::createNonNativeAsset($this->ngncAssetCode, $this->ngncIssuer);
            $source = $this->sdk->requestAccount($this->distributionSigner->publicKey);

            $payment = (new PaymentOperationBuilder(
                $this->platformWalletPublic,
                $asset,
                number_format($amount, 7, '.', ''),
            ))->build();

            $transaction = (new TransactionBuilder($source))
                ->addOperation($payment)
                ->addMemo(Memo::text($memo))
                ->build();

            $transaction->sign(KeyPair::fromSeed($this->distributionSigner->secret), $this->network);

            return $this->sdk->submitTransaction($transaction)->getHash();
        } catch (\Throwable $e) {
            Log::error('Stellar coin settlement failed', ['amount' => $amount, 'error' => $e->getMessage()]);

            throw new \RuntimeException('The on-chain payment could not be completed. Please try again.');
        }
    }

    /**
     * Reconcile a deposit's status with the anchor and persist the change.
     *
     * Idempotent: a deposit already recorded as completed is returned without another
     * anchor round-trip. On completion the on-chain hash and settled amount are captured.
     *
     * @throws \RuntimeException if the reference is unknown or not the vendor's
     */
    public function syncStatus(User $vendor, string $sep24Id): AnchorTransaction
    {
        $transaction = AnchorTransaction::query()
            ->where('sep24_id', $sep24Id)
            ->where('vendor_id', $vendor->id)
            ->first();

        if ($transaction === null) {
            throw new \RuntimeException('Deposit not found.');
        }

        if ($transaction->isCompleted()) {
            return $transaction;
        }

        $jwt = $this->anchor->authenticate($this->platformSigner);
        $remote = $this->anchor->getTransaction($jwt, $sep24Id);

        $transaction->status = $remote->status;

        if ($remote->status === Sep24StatusEnum::COMPLETED->value) {
            $transaction->amount = (float) ($remote->amountIn ?? $remote->amountOut ?? $transaction->amount);
            $transaction->stellar_tx_hash = $remote->stellarTransactionId;
            $transaction->completed_at = now();
        }

        $transaction->save();

        return $transaction;
    }

    public function markAmountMismatch(User $vendor, string $sep24Id): void
    {
        AnchorTransaction::query()
            ->where('sep24_id', $sep24Id)
            ->where('vendor_id', $vendor->id)
            ->whereNull('reconciliation')
            ->update(['reconciliation' => AnchorReconciliationEnum::AMOUNT_MISMATCH->value]);
    }
}
