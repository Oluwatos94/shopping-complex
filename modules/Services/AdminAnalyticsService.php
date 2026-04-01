<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;

final readonly class AdminAnalyticsService
{
    /**
     * Get platform-wide statistics using 3 aggregated queries.
     *
     * @return array<string, mixed>
     */
    public function getPlatformStats(): array
    {
        $userCounts = User::selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $onboardingCounts = VendorOnboarding::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'users' => [
                'total' => (int) $userCounts->sum(),
                'admins' => (int) ($userCounts['admin'] ?? 0),
                'vendors' => (int) ($userCounts['vendor'] ?? 0),
                'customers' => (int) ($userCounts['customer'] ?? 0),
            ],
            'products' => [
                'total' => Product::count(),
            ],
            'vendors' => [
                'approved' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::APPROVED->value] ?? 0),
                'pending_review' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::PENDING_REVIEW->value] ?? 0),
                'rejected' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::REJECTED->value] ?? 0),
                'draft' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::DRAFT->value] ?? 0),
            ],
        ];
    }

    /**
     * Get paginated user list with optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<User>
     */
    public function getUserList(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with('vendorOnboarding');

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get paginated pending vendor applications.
     *
     * @return LengthAwarePaginator<VendorOnboarding>
     */
    public function getPendingVendors(int $perPage = 20): LengthAwarePaginator
    {
        return VendorOnboarding::with('user')
            ->where('status', VendorOnboardingStatusEnum::PENDING_REVIEW)
            ->latest()
            ->paginate($perPage);
    }
}
