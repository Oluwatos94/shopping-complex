<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Discovery\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Discovery\Repositories\VendorRepository;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Repositories\UserRepository;

final readonly class VendorService
{
    public function __construct(
        private VendorRepository $vendorRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * Get nearby vendors with pagination and filtering
     *
     * @param  array<string, mixed>  $filters
     */
    public function getNearbyVendors(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->vendorRepository->findNearby($filters, $perPage);
    }

    /**
     * Find nearby vendors matching a product name — used by the WhatsApp bot.
     *
     * @return Collection<int, User>
     */
    public function findNearbyByQuery(float $lat, float $lng, string $query, float $radiusKm = 5.0, bool $loose = false): Collection
    {
        return $this->getNearbyVendors([
            'latitude' => $lat,
            'longitude' => $lng,
            'radius' => $radiusKm,
            'search' => $query,
            'search_loose' => $loose,
            'sort_by' => 'distance',
            'has_active_products' => true,
        ], perPage: 5)->getCollection();
    }

    /**
     * Find vendors matching a query anywhere — used by the WhatsApp bot when no
     * nearby vendors exist. When coords are given, distances are computed too.
     *
     * @return Collection<int, User>
     */
    public function findByQuery(string $query, int $limit = 5, ?float $lat = null, ?float $lng = null, bool $loose = false): Collection
    {
        $hasCoords = $lat !== null && $lng !== null;

        return $this->getNearbyVendors([
            'search' => $query,
            'search_loose' => $loose,
            'latitude' => $hasCoords ? $lat : null,
            'longitude' => $hasCoords ? $lng : null,
            'radius' => 0,
            'sort_by' => $hasCoords ? 'distance' : 'relevance',
            'has_active_products' => true,
        ], perPage: $limit)->getCollection();
    }

    /**
     * Find a vendor by ID — used by the WhatsApp bot.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getVendorById(int $vendorId): User
    {
        return $this->vendorRepository->find($vendorId);
    }

    /**
     * Get vendor statistics
     *
     * @return array<string, mixed>
     */
    public function getVendorStats(int $vendorId): array
    {
        return $this->vendorRepository->getStats($vendorId);
    }

    /**
     * Toggle follow/unfollow for a vendor.
     *
     * @return array{following: bool, followers_count: int}
     */
    public function toggleFollow(int $followerId, int $vendorId): array
    {
        $isFollowing = $this->userRepository->isFollowing($followerId, $vendorId);

        if ($isFollowing) {
            $this->userRepository->unfollowVendor($followerId, $vendorId);
        } else {
            $this->userRepository->followVendor($followerId, $vendorId);
        }

        return [
            'following' => ! $isFollowing,
            'followers_count' => $this->userRepository->getFollowersCount($vendorId),
        ];
    }
}
