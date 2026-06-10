<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorSubscription;
use ModulesShoppingComplex\Repositories\SubscriptionRepository;
use ModulesShoppingComplex\Services\Payments\CheckoutSession;
use ModulesShoppingComplex\Services\Payments\PaymentProviderManager;

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
        // Fast-path idempotency check — avoids hitting the gateway API on duplicate callbacks
        $existing = $this->subscriptionRepository->findByPaymentReference($reference);
        if ($existing !== null) {
            return $existing;
        }

        $result = $this->providers->for($method)->confirm($reference, $vendor);

        $plan = $this->subscriptionRepository->findActivePlanById($result->planId);
        if ($plan === null) {
            throw new \RuntimeException('The selected plan is no longer available.');
        }

        // Enforce, for every rail, that the settled amount covers the plan price. Paystack
        // fixes the amount at initialisation, but on-chain rails (Stellar/MPP) settle an
        // arbitrary amount, so this guard belongs in the shared lifecycle, not per-provider.
        if (round($result->amountPaid, 2) < round((float) $plan->price, 2)) {
            throw new \RuntimeException('Payment amount does not match the plan price.');
        }

        return DB::transaction(function () use ($vendor, $method, $result) {
            $existing = $this->subscriptionRepository->findByPaymentReference($result->reference);
            if ($existing !== null) {
                return $existing;
            }

            $currentSubscription = $this->subscriptionRepository->getActiveSubscriptionForUpdate($vendor->id);
            if ($currentSubscription !== null) {
                $this->subscriptionRepository->expireSubscription($currentSubscription);
            }

            try {
                return $this->subscriptionRepository->create([
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
        });
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
     * Idempotent: does nothing if the vendor already has an active subscription.
     * Safe to call inside an existing DB transaction (e.g. on vendor approval).
     *
     * @throws \RuntimeException if no active Free plan is configured
     */
    public function assignFreePlan(int $vendorId): VendorSubscription
    {
        // Use the locking variant — safe both inside and outside an existing transaction
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
