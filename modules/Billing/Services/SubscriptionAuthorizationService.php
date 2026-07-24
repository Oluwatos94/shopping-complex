<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Carbon\Carbon;
use ModulesShoppingComplex\Billing\Enums\SubscriptionAuthorizationStatusEnum;
use ModulesShoppingComplex\Billing\Models\SubscriptionAuthorization;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarWalletService;
use ModulesShoppingComplex\Identity\Models\User;

final readonly class SubscriptionAuthorizationService
{
    private const CONSENT_MONTHS = 12;

    public function __construct(
        private StellarWalletService $wallets,
    ) {}

    /**
     * @throws \RuntimeException if the custodial wallet cannot be provisioned
     */
    public function authorize(
        User $vendor,
        SubscriptionPlan $plan,
        ?float $monthlyCap = null,
        ?Carbon $validUntil = null,
    ): SubscriptionAuthorization {
        $this->wallets->getOrCreateForVendor($vendor);

        return SubscriptionAuthorization::query()->updateOrCreate(
            ['vendor_id' => $vendor->id],
            [
                'plan_id' => $plan->id,
                'monthly_cap' => $monthlyCap ?? (float) $plan->price,
                'valid_until' => $validUntil ?? now()->addMonths(self::CONSENT_MONTHS),
                'status' => SubscriptionAuthorizationStatusEnum::ACTIVE,
                'consent_at' => now(),
            ],
        );
    }

    public function revoke(User $vendor): void
    {
        SubscriptionAuthorization::query()
            ->where('vendor_id', $vendor->id)
            ->update(['status' => SubscriptionAuthorizationStatusEnum::REVOKED]);
    }

    public function chargeableFor(int $vendorId): ?SubscriptionAuthorization
    {
        $authorization = SubscriptionAuthorization::query()
            ->where('vendor_id', $vendorId)
            ->where('status', SubscriptionAuthorizationStatusEnum::ACTIVE)
            ->where('valid_until', '>', now())
            ->first();

        return $authorization;
    }
}
