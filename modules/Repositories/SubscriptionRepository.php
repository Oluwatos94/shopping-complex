<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use ModulesShoppingComplex\Models\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\VendorSubscription;

class SubscriptionRepository
{
    /**
     * Get all active subscription plans ordered by search priority.
     */
    public function getPlans(): Collection
    {
        return SubscriptionPlan::active()->orderBy('search_priority')->get();
    }

    /**
     * Find an active subscription plan by its slug.
     */
    public function findActivePlanBySlug(string $slug): ?SubscriptionPlan
    {
        return SubscriptionPlan::active()->where('slug', $slug)->first();
    }

    /**
     * Find an active subscription plan by its ID.
     */
    public function findActivePlanById(int $id): ?SubscriptionPlan
    {
        return SubscriptionPlan::active()->where('id', $id)->first();
    }

    /**
     * Get the vendor's current active subscription with its plan eager-loaded.
     */
    public function getActiveSubscription(int $vendorId): ?VendorSubscription
    {
        return VendorSubscription::where('vendor_id', $vendorId)
            ->where('status', VendorSubscriptionStatusEnum::ACTIVE)
            ->where('expires_at', '>', now())
            ->with('plan')
            ->latest('started_at')
            ->first();
    }

    public function getActiveSubscriptionForUpdate(int $vendorId): ?VendorSubscription
    {
        return VendorSubscription::where('vendor_id', $vendorId)
            ->where('status', VendorSubscriptionStatusEnum::ACTIVE)
            ->where('expires_at', '>', now())
            ->with('plan')
            ->lockForUpdate()
            ->first();
    }

    /**
     * Find a subscription by its Paystack payment reference.
     */
    public function findByPaymentReference(string $reference): ?VendorSubscription
    {
        return VendorSubscription::where('payment_reference', $reference)->first();
    }

    /**
     * Create a new vendor subscription record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): VendorSubscription
    {
        return VendorSubscription::create($data);
    }

    /**
     * Mark a subscription as expired.
     */
    public function expireSubscription(VendorSubscription $subscription): void
    {
        $subscription->update(['status' => VendorSubscriptionStatusEnum::EXPIRED]);
    }

    public function cancelSubscription(VendorSubscription $subscription): void
    {
        $subscription->update(['status' => VendorSubscriptionStatusEnum::CANCELLED]);
    }

    public function getOverdueActiveSubscriptions(): LazyCollection
    {
        return VendorSubscription::where('status', VendorSubscriptionStatusEnum::ACTIVE)
            ->where('expires_at', '<', now())
            ->with('vendor')
            ->lazy();
    }
}
