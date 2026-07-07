<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Events\SubscriptionPaymentSucceeded;
use ModulesShoppingComplex\Events\SubscriptionRenewalFailed;
use ModulesShoppingComplex\Events\SystemAlertEvent;
use ModulesShoppingComplex\Models\AnchorTransaction;
use ModulesShoppingComplex\Models\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\Enums\Sep24StatusEnum;
use ModulesShoppingComplex\Models\StellarWallet;
use ModulesShoppingComplex\Models\VendorSubscription;
use ModulesShoppingComplex\Repositories\SubscriptionRepository;
use ModulesShoppingComplex\Services\NotificationService;
use ModulesShoppingComplex\Services\Payments\Stellar\ChargeFailedException;
use ModulesShoppingComplex\Services\Payments\Stellar\Contracts\RecurringCharger;
use ModulesShoppingComplex\Services\SubscriptionAuthorizationService;

class RenewVendorSubscriptions implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function handle(
        SubscriptionRepository $subscriptions,
        SubscriptionAuthorizationService $authorizations,
        RecurringCharger $charger,
        NotificationService $notifications,
    ): void {
        $due = $subscriptions->getStellarSubscriptionsDueForRenewal(now());

        foreach ($due as $subscription) {
            $this->renewOne($subscription, $subscriptions, $authorizations, $charger, $notifications);
        }
    }

    private function renewOne(
        VendorSubscription $subscription,
        SubscriptionRepository $subscriptions,
        SubscriptionAuthorizationService $authorizations,
        RecurringCharger $charger,
        NotificationService $notifications,
    ): void {
        $vendor = $subscription->vendor;
        $plan = $subscription->plan;
        if ($vendor === null || $plan === null) {
            return;
        }

        $authorization = $authorizations->chargeableFor($vendor->id);
        $price = (float) $plan->price;
        if ($authorization === null || $price > (float) $authorization->monthly_cap) {
            return; // no mandate to charge → let the subscription lapse via the expiry job
        }

        $period = $subscription->expires_at->format('Y-m-d');

        try {
            $charge = AnchorTransaction::query()->create([
                'vendor_id' => $vendor->id,
                'plan_id' => $plan->id,
                'kind' => AnchorTransactionKindEnum::MPP_CHARGE,
                'billing_period' => $period,
                'status' => Sep24StatusEnum::PENDING_STELLAR->value,
                'amount' => $price,
                'started_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $this->reconcileAlreadyCharged($subscription, $subscriptions, $period);

            return;
        }

        $wallet = StellarWallet::query()->where('vendor_id', $vendor->id)->first();
        if ($wallet === null) {
            $this->markFailed($charge);

            return;
        }

        try {
            $hash = $charger->charge($wallet, $price);
        } catch (ChargeFailedException) {
            $this->markFailed($charge);
            $this->notifyFailure($notifications, $subscription);

            return;
        }

        $charge->update([
            'status' => Sep24StatusEnum::COMPLETED->value,
            'stellar_tx_hash' => $hash,
            'completed_at' => now(),
        ]);

        $subscriptions->extendSubscription($subscription, $subscription->expires_at->copy()->addMonth(), $hash, $price);
        $this->notifySuccess($notifications, $subscription, $price);
    }

    private function reconcileAlreadyCharged(
        VendorSubscription $subscription,
        SubscriptionRepository $subscriptions,
        string $period,
    ): void {
        if ($subscription->expires_at->format('Y-m-d') !== $period) {
            return;
        }

        $existing = AnchorTransaction::query()
            ->where('vendor_id', $subscription->vendor_id)
            ->where('kind', AnchorTransactionKindEnum::MPP_CHARGE)
            ->where('billing_period', $period)
            ->first();

        if ($existing === null || ! $existing->isCompleted() || $existing->stellar_tx_hash === null) {
            return;
        }

        $subscriptions->extendSubscription(
            $subscription,
            $subscription->expires_at->copy()->addMonth(),
            $existing->stellar_tx_hash,
            (float) $existing->amount,
        );
    }

    private function markFailed(AnchorTransaction $charge): void
    {
        $charge->update([
            'status' => Sep24StatusEnum::ERROR->value,
            'completed_at' => now(),
        ]);
    }

    private function notifySuccess(NotificationService $notifications, VendorSubscription $subscription, float $amount): void
    {
        try {
            $notifications->send(new SystemAlertEvent(
                recipient: $subscription->vendor,
                message: sprintf('Your Jiidaa subscription payment of %s is confirmed — your plan is renewed for another month.', $this->formatNaira($amount)),
                alertLevel: 'info',
                data: ['action' => 'subscription_renewed'],
            ));
        } catch (\Throwable $e) {
            Log::warning('Renewal in-app notification failed', ['vendor_id' => $subscription->vendor_id, 'error' => $e->getMessage()]);
        }

        try {
            SubscriptionPaymentSucceeded::dispatch(
                $subscription->vendor,
                $amount,
                PaymentMethodEnum::STELLAR,
                true,
            );
        } catch (\Throwable $e) {
            Log::warning('Renewal WhatsApp notification failed', ['vendor_id' => $subscription->vendor_id, 'error' => $e->getMessage()]);
        }
    }

    private function notifyFailure(NotificationService $notifications, VendorSubscription $subscription): void
    {
        try {
            $notifications->send(new SystemAlertEvent(
                recipient: $subscription->vendor,
                message: '⚠️ Your subscription renewal failed — top up your balance to keep your store active.',
                alertLevel: 'warning',
                data: ['action' => 'renew_subscription'],
            ));
        } catch (\Throwable $e) {
            Log::warning('Renewal in-app notification failed', ['vendor_id' => $subscription->vendor_id, 'error' => $e->getMessage()]);
        }

        try {
            SubscriptionRenewalFailed::dispatch($subscription->vendor);
        } catch (\Throwable $e) {
            Log::warning('Renewal WhatsApp notification failed', ['vendor_id' => $subscription->vendor_id, 'error' => $e->getMessage()]);
        }
    }

    private function formatNaira(float $amount): string
    {
        return '₦'.number_format($amount, 2);
    }
}
