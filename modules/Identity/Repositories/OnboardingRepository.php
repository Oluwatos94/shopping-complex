<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Identity\Models\VendorOnboarding;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;

class OnboardingRepository extends BasePageRepository
{
    /**
     * Find onboarding by user ID.
     */
    public function findOnboardingByUserId(int $userId): ?VendorOnboarding
    {
        return VendorOnboarding::where('user_id', $userId)->first();
    }

    /**
     * Create or update onboarding record for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOrCreateOnboarding(int $userId, array $data): VendorOnboarding
    {
        return VendorOnboarding::updateOrCreate(
            ['user_id' => $userId],
            $data
        );
    }

    /**
     * Update an onboarding record.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOnboarding(VendorOnboarding $onboarding, array $data): VendorOnboarding
    {
        $onboarding->update($data);

        return $onboarding;
    }

    /**
     * Get onboardings pending review for admin.
     *
     * @param  array<string>  $relations
     */
    public function getPendingOnboardings(int $perPage = 20, array $relations = []): LengthAwarePaginator
    {
        $query = VendorOnboarding::query()
            ->where('status', VendorOnboardingStatusEnum::PENDING_REVIEW)
            ->orderBy('created_at', 'asc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Check if user has an approved onboarding.
     */
    public function hasApprovedOnboarding(int $userId): bool
    {
        return VendorOnboarding::where('user_id', $userId)
            ->where('status', VendorOnboardingStatusEnum::APPROVED)
            ->exists();
    }
}
