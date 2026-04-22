<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Models\Address;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;

class VendorRepository extends BasePageRepository
{
    /**
     * Find nearby vendors with pagination and filtering
     *
     * @param  array<string, mixed>  $filters
     */
    public function findNearby(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $latitude = $filters['latitude'] ?? null;
        $longitude = $filters['longitude'] ?? null;
        $radius = $filters['radius'] ?? 5; // Default 5km radius
        $search = $filters['search'] ?? null;
        $sortBy = $filters['sort_by'] ?? 'distance';

        $addressTable = Address::getTableName();
        $query = User::query()
            ->where('role', 'vendor')
            ->with(['media', 'vendorOnboarding']);

        // If GPS coordinates are provided, join addresses and calculate distance using Haversine formula
        $haversine = "(6371 * acos(
            cos(radians(?)) *
            cos(radians(COALESCE({$addressTable}.latitude, 0))) *
            cos(radians(COALESCE({$addressTable}.longitude, 0)) - radians(?)) +
            sin(radians(?)) *
            sin(radians(COALESCE({$addressTable}.latitude, 0)))
        ))";

        if ($latitude && $longitude) {
            $query->leftJoin($addressTable, 'users.id', '=', "{$addressTable}.user_id")
                ->selectRaw("users.*, {$haversine} AS distance_km", [$latitude, $longitude, $latitude]);

            // Only include vendors within radius
            if ($radius) {
                $query->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radius]);
            }
        } else {
            // No location provided — distance is unknown
            $query->select('users.*')
                ->selectRaw('NULL as distance_km');
        }

        // withCount/withAvg must come AFTER select/selectRaw to avoid being cleared
        $query->withCount(['products', 'products as active_products_count' => fn ($q) => $q->where('is_active', true)]);

        if ($sortBy === 'rating') {
            $query->withAvg('reviews', 'rating');
        }

        if ($search) {
            $escapedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('business_name', 'like', "%{$escapedSearch}%")
                    ->orWhere('name', 'like', "%{$escapedSearch}%")
                    ->orWhereHas('products', fn ($p) => $p->where('is_active', true)
                        ->where(fn ($p2) => $p2
                            ->where('name', 'like', "%{$escapedSearch}%")
                            ->orWhere('description', 'like', "%{$escapedSearch}%")
                        )
                    )
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$escapedSearch}%"));
            });
        }

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('email_verified_at');
        }

        match ($sortBy) {
            'distance' => $latitude && $longitude
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderBy('created_at', 'desc'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('created_at'),
            'newest'  => $query->orderBy('created_at', 'desc'),
            default   => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get vendor statistics
     *
     * @return array<string, mixed>
     */
    public function getStats(int $vendorId): array
    {
        $vendor = User::query()
            ->where('id', $vendorId)
            ->where('role', 'vendor')
            ->withCount(['products', 'products as active_products_count' => fn ($q) => $q->where('is_active', true)])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->firstOrFail();

        return [
            'total_products' => $vendor->products_count,
            'active_products' => $vendor->active_products_count,
            'total_reviews' => $vendor->reviews_count,
            'avg_rating' => $vendor->reviews_avg_rating ?? 0,
        ];
    }

    /**
     * Find a vendor by ID
     *
     * @param  array<string>  $relations  Relations to eager load
     *
     * @throws ModelNotFoundException
     */
    public function find(int $id, array $relations = []): User
    {
        $query = User::query()->where('role', 'vendor');

        if (! empty($relations)) {
            $query->with($relations);
        }

        $vendor = $query->find($id);

        if (! $vendor) {
            throw new ModelNotFoundException("Vendor with ID {$id} not found.");
        }

        return $vendor;
    }

    /**
     * Get all active vendors
     *
     * @param  array<string>  $relations
     * @return Collection<int, User>
     */
    public function getActive(array $relations = []): Collection
    {
        $query = User::query()->where('role', 'vendor')->whereNotNull('email_verified_at');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->get();
    }

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
