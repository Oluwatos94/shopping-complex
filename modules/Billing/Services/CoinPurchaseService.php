<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Billing\Data\CoinPack;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Billing\Events\CoinPackPurchased;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;
use ModulesShoppingComplex\Billing\Payments\CheckoutSession;
use ModulesShoppingComplex\Billing\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Billing\Payments\PaystackUnavailableException;
use ModulesShoppingComplex\Identity\Models\User;

final class CoinPurchaseService
{
    public const CHANNEL = 'coin_pack';

    public function __construct(
        private readonly PaystackClient $paystack,
        private readonly CoinWalletService $wallet,
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
