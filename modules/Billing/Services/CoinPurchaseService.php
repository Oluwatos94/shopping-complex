<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Billing\Data\CoinPack;
use ModulesShoppingComplex\Billing\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Billing\Enums\Sep24StatusEnum;
use ModulesShoppingComplex\Billing\Events\CoinPackPurchased;
use ModulesShoppingComplex\Billing\Models\AnchorTransaction;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;
use ModulesShoppingComplex\Billing\Payments\CheckoutSession;
use ModulesShoppingComplex\Billing\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Billing\Payments\PaystackUnavailableException;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarDepositService;
use ModulesShoppingComplex\Identity\Models\User;

final class CoinPurchaseService
{
    public const CHANNEL = 'coin_pack';

    public function __construct(
        private readonly PaystackClient $paystack,
        private readonly CoinWalletService $wallet,
        private readonly StellarDepositService $deposits,
    ) {}

    public function startCheckout(User $vendor, CoinPack $pack, string $callbackUrl): CheckoutSession
    {
        $reference = 'coin_'.Str::uuid()->getHex();

        $purchase = CoinPurchase::create([
            'vendor_id' => $vendor->id,
            'pack' => $pack->key,
            'price' => $pack->price,
            'coins' => $pack->coins,
            'bonus_coins' => $pack->bonusCoins,
            'reference' => $reference,
            'status' => CoinPurchaseStatusEnum::PENDING,
        ]);

        try {
            $authorizationUrl = $this->paystack->initializeTransaction(
                email: $vendor->email,
                amountInKobo: $pack->priceInKobo(),
                metadata: [
                    'type' => self::CHANNEL,
                    'vendor_id' => $vendor->id,
                    'pack' => $pack->key,
                ],
                callbackUrl: $callbackUrl,
                reference: $reference,
            );
        } catch (PaystackUnavailableException $e) {

            throw $e;
        } catch (\Throwable $e) {
            $purchase->delete();

            throw $e;
        }

        return new CheckoutSession(CheckoutTypeEnum::REDIRECT, $authorizationUrl, $reference);
    }

    /**
     * Verify a settled payment and credit the coins once. Safe to call from both
     * the webhook and the redirect callback, and safe to call repeatedly.
     *
     * @throws \RuntimeException if the reference is unknown, unverifiable, unsuccessful, or tampered with
     */
    public function fulfill(string $reference, User $vendor): CoinPurchase
    {
        $purchase = CoinPurchase::where('reference', $reference)->first();

        if ($purchase === null) {
            throw new \RuntimeException('Unknown coin purchase reference.');
        }

        if ($purchase->vendor_id !== $vendor->id) {
            throw new \RuntimeException('Payment reference does not belong to your account.');
        }

        if ($purchase->isCompleted()) {
            return $purchase;
        }

        $data = $this->paystack->verifyTransaction($reference);
        $this->assertPaidInFull($purchase, $data);

        return $this->creditOnce($purchase, $vendor);
    }

    public function settleWithStellar(User $vendor, CoinPack $pack): array
    {
        $purchase = CoinPurchase::create([
            'vendor_id' => $vendor->id,
            'pack' => $pack->key,
            'price' => $pack->price,
            'coins' => $pack->coins,
            'bonus_coins' => $pack->bonusCoins,
            'reference' => 'coin_'.Str::uuid()->getHex(),
            'status' => CoinPurchaseStatusEnum::PENDING,
        ]);

        try {
            $txHash = $this->deposits->settleNgncToPlatform((float) $pack->price, 'coins-'.$purchase->id);
        } catch (\Throwable $e) {
            $purchase->delete();

            throw $e;
        }

        AnchorTransaction::query()->create([
            'vendor_id' => $vendor->id,
            'coin_purchase_id' => $purchase->id,
            'kind' => AnchorTransactionKindEnum::COIN_DEPOSIT,
            'status' => Sep24StatusEnum::COMPLETED->value,
            'amount' => $pack->price,
            'stellar_tx_hash' => $txHash,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return ['purchase' => $this->creditOnce($purchase, $vendor), 'tx_hash' => $txHash];
    }

    /**
     * Flip a verified purchase to completed and credit its coins + bonus exactly once, under a row
     * lock so a racing caller (webhook vs redirect, or a second status poll) cannot double-credit.
     */
    private function creditOnce(CoinPurchase $purchase, User $vendor): CoinPurchase
    {
        $credited = false;

        $purchase = DB::transaction(function () use ($purchase, $vendor, &$credited): CoinPurchase {
            $locked = CoinPurchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCompleted()) {
                return $locked;
            }

            $this->wallet->credit($vendor, CoinLedgerTypeEnum::PURCHASE, $locked->coins, $locked);

            if ($locked->bonus_coins > 0) {
                $this->wallet->credit($vendor, CoinLedgerTypeEnum::BONUS, $locked->bonus_coins, $locked);
            }

            $locked->forceFill([
                'status' => CoinPurchaseStatusEnum::COMPLETED,
                'paid_at' => now(),
            ])->save();

            $credited = true;

            return $locked;
        });

        if ($credited) {
            CoinPackPurchased::dispatch($purchase, $this->wallet->balance($vendor));
        }

        return $purchase;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertPaidInFull(CoinPurchase $purchase, array $data): void
    {
        $paidKobo = (int) ($data['amount'] ?? 0);

        if ($paidKobo !== $purchase->price * 100) {
            throw new \RuntimeException('Payment amount does not match the coin pack price.');
        }

        $metadataVendorId = (int) ($data['metadata']['vendor_id'] ?? 0);

        if ($metadataVendorId !== $purchase->vendor_id) {
            throw new \RuntimeException('Payment metadata does not match this purchase.');
        }
    }
}
