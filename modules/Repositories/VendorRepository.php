<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Models\User;

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

        $query = User::query()->where('role', 'vendor')->with(['products', 'media']);

        // If GPS coordinates are provided, calculate distance using Haversine formula
        // if ($latitude && $longitude) {
        //     $query->selectRaw(
        //         'users.*,
        //         (6371 * acos(
        //             cos(radians(?)) *
        //             cos(radians(COALESCE(latitude, 0))) *
        //             cos(radians(COALESCE(longitude, 0)) - radians(?)) +
        //             sin(radians(?)) *
        //             sin(radians(COALESCE(latitude, 0)))
        //         )) AS distance_km',
        //         [$latitude, $longitude, $latitude]
        //     );

        //     // Only include vendors within radius
        //     if ($radius) {
        //         $query->havingRaw('distance_km <= ?', [$radius]);
        //     }
        // } else {
        //     // Fallback when no location provided
        //     $query->select('users.*')
        //         ->selectRaw('0 as distance_km');
        // }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (isset($filters['verified_only']) && $filters['verified_only']) {
            $query->whereNotNull('email_verified_at');
        }

        match ($sortBy) {
            'distance' => $latitude && $longitude
                ? $query->orderBy('distance_km', 'asc')
                : $query->orderBy('created_at', 'desc'),
            'rating' => $query->orderBy('created_at', 'desc'), // TODO: Use actual rating when reviews are implemented
            'response_time' => $query->orderBy('created_at', 'desc'), // TODO: Add response_time field
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
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
        $vendor = $this->find($vendorId);

        return [
            'total_products' => $vendor->products()->count(),
            'active_products' => $vendor->products()->where('is_active', true)->count(),
            'total_reviews' => $vendor->reviews()->count(),
            'avg_rating' => $vendor->reviews()->avg('rating') ?? 0,
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
}
