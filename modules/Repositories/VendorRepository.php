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
        $categoryId = $filters['category_id'] ?? null;

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

        if ($search !== null && $search !== '') {
            $this->applySearchTerms(
                $query,
                $this->searchTerms((string) $search),
                (bool) ($filters['search_loose'] ?? true),
            );
        }

        if (! empty($filters['has_active_products'])) {
            $query->whereHas('products', fn ($p) => $p->where('is_active', true));
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('email_verified_at');
        }

        match ($sortBy) {
            'distance' => $latitude && $longitude
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderBy('created_at', 'desc'),
            'rating' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('created_at'),
            'relevance' => $query->orderByDesc('active_products_count')->orderByDesc('created_at'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Every term must match the vendor somewhere (AND). Strict mode matches
     * business/vendor name, product names and tags; loose mode widens to
     * product descriptions and category names.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @param  array<int, string>  $terms
     */
    private function applySearchTerms($query, array $terms, bool $loose): void
    {
        foreach ($terms as $term) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);

            $query->where(function ($q) use ($escaped, $loose) {
                $q->where('business_name', 'like', "%{$escaped}%")
                    ->orWhere('name', 'like', "%{$escaped}%")
                    ->orWhereHas('products', fn ($p) => $p->where('is_active', true)
                        ->where(fn ($p2) => $p2
                            ->where('name', 'like', "%{$escaped}%")
                            ->orWhere('tags', 'like', "%{$escaped}%")
                            ->when($loose, fn ($p3) => $p3->orWhere('description', 'like', "%{$escaped}%"))
                        )
                    );

                if ($loose) {
                    $q->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$escaped}%"));
                }
            });
        }
    }

    /**
     * Break a free-text search into meaningful lowercase terms, dropping noise
     * words so multi-word queries still match. Falls back to the raw query when
     * everything is filtered out, so we never accidentally match every vendor.
     *
     * @return array<int, string>
     */
    private function searchTerms(string $search): array
    {
        $stopWords = [
            'a', 'an', 'and', 'the', 'or', 'of', 'for', 'to', 'in', 'on', 'at',
            'me', 'my', 'i', 'is', 'are', 'near', 'nearby', 'around', 'who',
            'sell', 'sells', 'selling', 'seller', 'sellers', 'vendor', 'vendors',
            'shop', 'shops', 'store', 'stores', 'some', 'any', 'find', 'need', 'want',
            'service', 'services', 'looking', 'please', 'get', 'show',
            'where', 'how', 'what', 'buy', 'can',
        ];

        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(trim($search))) ?: [];

        $terms = array_values(array_unique(array_filter(
            $words,
            fn (string $w) => mb_strlen($w) >= 2 && ! in_array($w, $stopWords, true),
        )));

        if ($terms !== []) {
            return $terms;
        }

        $raw = trim($search);

        return $raw !== '' ? [mb_strtolower($raw)] : [];
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
