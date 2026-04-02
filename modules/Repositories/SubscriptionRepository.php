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
     * Get all active subscription plans.
     */
    public function getPlans(): Collection
    {
        return SubscriptionPlan::active()->orderBy('search_priority')->get();
    }

    /**
     * Find a subscription plan by slug.
     */
    public function findPlanBySlug(string $slug): ?SubscriptionPlan
    {
        return SubscriptionPlan::active()->where('slug', $slug)->first();
    }

    /**
     * Get the vendor's current active subscription with its plan.
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

    /**
     * Create a new subscription for a vendor.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): VendorSubscription
    {
        return VendorSubscription::create($data);
    }

    /**
     * Mark a vendor's active subscription as expired.
     */
    public function expireSubscription(VendorSubscription $subscription): void
    {
        $subscription->update(['status' => VendorSubscriptionStatusEnum::EXPIRED]);
    }

    /**
     * Get all subscriptions that are past their expiry date but still marked active.
     * Returns a lazy collection to avoid loading all records into memory at once.
     */
    public function getOverdueActiveSubscriptions(): LazyCollection
    {
        return VendorSubscription::where('status', VendorSubscriptionStatusEnum::ACTIVE)
            ->where('expires_at', '<', now())
            ->with('vendor')
            ->lazy();
    }
}
