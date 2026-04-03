<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorSubscription;
use ModulesShoppingComplex\Repositories\SubscriptionRepository;

final readonly class SubscriptionService
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository,
        private PaystackClient $paystackClient,
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
     * Initiate a Paystack payment for a vendor plan.
     * Returns the Paystack authorization URL to redirect the vendor to.
     *
     * @throws \RuntimeException
     */
    public function initiatePayment(User $vendor, SubscriptionPlan $plan): string
    {
        // Amount must be in kobo (Paystack uses the smallest currency unit)
        $amountInKobo = (int) round($plan->price * 100);

        return $this->paystackClient->initializeTransaction(
            email: $vendor->email,
            amountInKobo: $amountInKobo,
            metadata: [
                'vendor_id' => $vendor->id,
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
            ],
            callbackUrl: route('vendor.subscription.callback'),
        );
    }

    /**
     * Verify a Paystack callback reference and activate the vendor's subscription.
     *
     * Security: validates that the reference belongs to the authenticated vendor.
     * Idempotent: calling with the same reference twice returns the existing subscription.
     * Race-safe: handles concurrent duplicate callbacks via unique-constraint recovery.
     *
     * @throws \RuntimeException
     */
    public function handlePaystackCallback(string $reference, int $authenticatedVendorId): VendorSubscription
    {
        // Fast-path idempotency check — avoids hitting the Paystack API on duplicate callbacks
        $existing = $this->subscriptionRepository->findByPaymentReference($reference);
        if ($existing !== null) {
            return $existing;
        }

        $paystackData = $this->paystackClient->verifyTransaction($reference);

        $metadata = $paystackData['metadata'] ?? [];
        $vendorId = (int) ($metadata['vendor_id'] ?? 0);
        $planId = (int) ($metadata['plan_id'] ?? 0);

        if (! $vendorId || ! $planId) {
            throw new \RuntimeException('Invalid payment metadata.');
        }

        if ($vendorId !== $authenticatedVendorId) {
            throw new \RuntimeException('Payment reference does not belong to your account.');
        }

        $plan = SubscriptionPlan::where('id', $planId)->where('is_active', true)->first();
        if ($plan === null) {
            throw new \RuntimeException('The selected plan is no longer available.');
        }

        $amountPaid = $paystackData['amount'] / 100; // kobo → naira

        return DB::transaction(function () use ($vendorId, $planId, $reference, $amountPaid) {
            $existing = $this->subscriptionRepository->findByPaymentReference($reference);
            if ($existing !== null) {
                return $existing;
            }

            $currentSubscription = $this->subscriptionRepository->getActiveSubscriptionForUpdate($vendorId);
            if ($currentSubscription !== null) {
                $this->subscriptionRepository->expireSubscription($currentSubscription);
            }

            try {
                return $this->subscriptionRepository->create([
                    'vendor_id' => $vendorId,
                    'plan_id' => $planId,
                    'status' => VendorSubscriptionStatusEnum::ACTIVE,
                    'started_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'payment_reference' => $reference,
                    'amount_paid' => $amountPaid,
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request created the subscription between our check and our insert
                return $this->subscriptionRepository->findByPaymentReference($reference);
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
