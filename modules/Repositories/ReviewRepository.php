<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Models\Enums\UserEnum;
use ModulesShoppingComplex\Models\Review;

class ReviewRepository extends BasePageRepository
{
    /**
     * Get reviews for a vendor with pagination.
     *
     * @param  array<int|string, string|\Closure>  $relations
     */
    public function getForVendor(
        int $vendorId,
        ?ReviewStatusEnum $status = null,
        int $perPage = 15,
        array $relations = []
    ): LengthAwarePaginator {
        $query = Review::query()
            ->where('vendor_id', $vendorId)
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get all reviews pending moderation with pagination.
     *
     * @param  array<string>  $relations
     */
    public function getPendingModeration(int $perPage = 20, array $relations = []): LengthAwarePaginator
    {
        $query = Review::query()
            ->where('status', ReviewStatusEnum::PENDING)
            ->orderBy('created_at', 'asc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new review.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Review
    {
        return Review::create($data);
    }

    /**
     * Find a review by ID.
     *
     * @param  array<string>  $relations
     */
    public function find(int $id, array $relations = []): ?Review
    {
        $query = Review::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Find a review by customer and vendor (includes soft-deleted to prevent duplicates).
     */
    public function findByCustomerAndVendor(int $customerId, int $vendorId): ?Review
    {
        return Review::withTrashed()
            ->where('customer_id', $customerId)
            ->where('vendor_id', $vendorId)
            ->first();
    }

    /**
     * Update a review.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Review $review, array $data): Review
    {
        $review->update($data);

        return $review;
    }

    /**
     * Delete a review (soft delete).
     */
    public function delete(Review $review): bool
    {
        return $review->delete() ?? false;
    }

    /**
     * Get vendor's rating stats and distribution in a single query.
     *
     * @return array{average: float, count: int, distribution: array<int, int>}
     */
    public function getVendorRatingStatsWithDistribution(int $vendorId): array
    {
        $rows = Review::query()
            ->where('vendor_id', $vendorId)
            ->where('status', ReviewStatusEnum::APPROVED)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $totalCount = array_sum($rows);
        $weightedSum = 0;
        $distribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $rows[$i] ?? 0;
            $weightedSum += $i * $distribution[$i];
        }

        return [
            'average' => $totalCount > 0 ? round($weightedSum / $totalCount, 2) : 0.0,
            'count' => $totalCount,
            'distribution' => $distribution,
        ];
    }

    public function hasCustomerInteractedWithVendor(int $customerId, int $vendorId): bool
    {
        return DB::table('conversations')
            ->where('customer_id', $customerId)
            ->where('vendor_id', $vendorId)
            ->exists();
    }

    /**
     * Check if a vendor exists.
     */
    public function vendorExists(int $vendorId): bool
    {
        return DB::table('users')
            ->where('id', $vendorId)
            ->where('role', UserEnum::VENDOR->value)
            ->exists();
    }

    public function incrementHelpfulCount(int $reviewId): void
    {
        Review::query()
            ->where('id', $reviewId)
            ->increment('helpful_count');
    }

    public function decrementHelpfulCount(int $reviewId): void
    {
        Review::query()
            ->where('id', $reviewId)
            ->where('helpful_count', '>', 0)
            ->decrement('helpful_count');
    }

    public function incrementNotHelpfulCount(int $reviewId): void
    {
        Review::query()
            ->where('id', $reviewId)
            ->increment('not_helpful_count');
    }

    public function decrementNotHelpfulCount(int $reviewId): void
    {
        Review::query()
            ->where('id', $reviewId)
            ->where('not_helpful_count', '>', 0)
            ->decrement('not_helpful_count');
    }

    public function getByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return Review::query()
            ->where('customer_id', $customerId)
            ->with(['vendor', 'conversation'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
