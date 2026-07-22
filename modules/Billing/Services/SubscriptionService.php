<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Billing\Events\SubscriptionPaymentSucceeded;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Models\VendorSubscription;
use ModulesShoppingComplex\Billing\Payments\AmountMismatchException;
use ModulesShoppingComplex\Billing\Payments\CheckoutSession;
use ModulesShoppingComplex\Billing\Payments\PaymentProviderManager;
use ModulesShoppingComplex\Billing\Repositories\SubscriptionRepository;
use ModulesShoppingComplex\Models\User;

final readonly class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private PaymentProviderManager $providers,
    ) {}

    /**
     * Get all active subscription plans.
     */
    public function getPlans(): Collection
    {
        return $this->subscriptionRepository->getPlans();
    }

    /**
     * Get the vendor's current active subscription with its plan.
     */
    public function getVendorSubscription(int $vendorId): ?VendorSubscription
    {
        return $this->subscriptionRepository->getActiveSubscription($vendorId);
    }

    /**
     * Start a payment for a vendor plan on the chosen rail.
     * Returns a CheckoutSession describing where to send the vendor to pay.
     *
     * @throws \RuntimeException
     */
    public function initiatePayment(User $vendor, SubscriptionPlan $plan, PaymentMethodEnum $method): CheckoutSession
    {
        return $this->providers->for($method)->startCheckout($vendor, $plan);
    }

    /**
     * Verify a completed payment on the given rail and activate the vendor's subscription.
     *
     * Provider-agnostic: the provider handles verification + ownership; this method owns the
     * subscription lifecycle.
     * Security: the provider validates that the reference belongs to the authenticated vendor.
     * Idempotent: calling with the same reference twice returns the existing subscription.
     * Race-safe: handles concurrent duplicate callbacks via unique-constraint recovery.
     *
     * @throws \RuntimeException
     */
    public function handleCallback(PaymentMethodEnum $method, string $reference, User $vendor): VendorSubscription
    {
        $existing = $this->subscriptionRepository->findByPaymentReference($reference);
        if ($existing !== null) {
            return $existing;
        }

        $result = $this->providers->for($method)->confirm($reference, $vendor);

        $plan = $this->subscriptionRepository->findActivePlanById($result->planId);
        if ($plan === null) {
            throw new \RuntimeException('The selected plan is no longer available.');
        }

        if (abs($result->amountPaid - (float) $plan->price) > 0.01) {
            throw new AmountMismatchException((float) $plan->price, $result->amountPaid);
        }

        $activated = false;

        $subscription = DB::transaction(function () use ($vendor, $method, $result, &$activated) {

            $currentSubscription = $this->subscriptionRepository->getActiveSubscriptionForUpdate($vendor->id);

            $existing = $this->subscriptionRepository->findByPaymentReference($result->reference);
            if ($existing !== null) {
                return $existing;
            }

            try {
                $subscription = $this->subscriptionRepository->create([
                    'vendor_id' => $vendor->id,
                    'plan_id' => $result->planId,
                    'status' => VendorSubscriptionStatusEnum::ACTIVE,
                    'payment_method' => $method,
                    'started_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'payment_reference' => $result->reference,
                    'amount_paid' => $result->amountPaid,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request created the subscription between our check and our insert
                return $this->subscriptionRepository->findByPaymentReference($result->reference);
            }

            if ($currentSubscription !== null) {
                $this->subscriptionRepository->expireSubscription($currentSubscription);
            }

            $activated = true;

            return $subscription;
        });

        if ($activated) {
            SubscriptionPaymentSucceeded::dispatch($vendor, $result->amountPaid, $method);
        }

        return $subscription;
    }

    /**
     * Cancel a vendor's active subscription.
     *
     * @throws \RuntimeException
     */
    public function cancelSubscription(User $vendor): VendorSubscription
    {
        return DB::transaction(function () use ($vendor) {
            $subscription = $this->subscriptionRepository->getActiveSubscriptionForUpdate($vendor->id);

            if ($subscription === null) {
                throw new \RuntimeException('No active subscription found.');
            }

            $this->subscriptionRepository->cancelSubscription($subscription);

            return $subscription->fresh();
        });
    }

    /**
     * Assign the Free plan to a vendor.
     * Safe to call inside an existing DB transaction (e.g. on vendor approval).
     *
     * @throws \RuntimeException if no active Free plan is configured
     */
    public function assignFreePlan(int $vendorId): VendorSubscription
    {
        $existing = $this->subscriptionRepository->getActiveSubscriptionForUpdate($vendorId);
        if ($existing !== null) {
            return $existing;
        }

        $freePlan = $this->subscriptionRepository->findActivePlanBySlug('free');

        if ($freePlan === null) {
            throw new \RuntimeException('Free subscription plan is not configured.');
        }

        return $this->subscriptionRepository->create([
            'vendor_id' => $vendorId,
            'plan_id' => $freePlan->id,
            'status' => VendorSubscriptionStatusEnum::ACTIVE,
            'started_at' => now(),
            'expires_at' => Carbon::create(2099, 12, 31, 23, 59, 59),
            'payment_reference' => null,
            'amount_paid' => 0.0,
        ]);
    }
}
