<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Models\Enums\UserEnum;
use ModulesShoppingComplex\Models\Enums\WhatsAppInteractionEventEnum;
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

    /**
     * Get rating stats for multiple vendors in a single query.
     *
     * @param  array<int>  $vendorIds
     * @return array<int, array{average: float, count: int}>
     */
    public function getBulkVendorRatingStats(array $vendorIds): array
    {
        if (empty($vendorIds)) {
            return [];
        }

        $rows = Review::query()
            ->whereIn('vendor_id', $vendorIds)
            ->where('status', ReviewStatusEnum::APPROVED)
            ->selectRaw('vendor_id, rating, COUNT(*) as count')
            ->groupBy('vendor_id', 'rating')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->vendor_id][$row->rating] = (int) $row->getAttribute('count');
        }

        $result = [];
        foreach ($vendorIds as $vendorId) {
            $ratings = $grouped[$vendorId] ?? [];
            $totalCount = array_sum($ratings);
            $weightedSum = 0;
            for ($i = 1; $i <= 5; $i++) {
                $weightedSum += $i * ($ratings[$i] ?? 0);
            }
            $result[$vendorId] = [
                'average' => $totalCount > 0 ? round($weightedSum / $totalCount, 2) : 0.0,
                'count' => $totalCount,
            ];
        }

        return $result;
    }

    public function hasCustomerInteractedWithVendor(int $customerId, int $vendorId): bool
    {
        $hasConversation = DB::table('conversations')
            ->where('customer_id', $customerId)
            ->where('vendor_id', $vendorId)
            ->exists();

        if ($hasConversation) {
            return true;
        }

        if ($this->hasWebContactWithVendor($customerId, $vendorId)) {
            return true;
        }

        return $this->hasWhatsAppContactWithVendor($customerId, $vendorId);
    }

    public function hasWebContactWithVendor(int $customerId, int $vendorId): bool
    {
        return DB::table('vendor_contacts')
            ->where('customer_id', $customerId)
            ->where('vendor_id', $vendorId)
            ->exists();
    }

    /**
     * Whether the customer reached out to the vendor through the WhatsApp bot.
     *
     * The bot only knows users by their WhatsApp phone number, so we match the
     * customer's stored phone numbers against the CONTACT_REQUESTED interactions
     * logged for this vendor.
     */
    public function hasWhatsAppContactWithVendor(int $customerId, int $vendorId): bool
    {
        $user = DB::table('users')
            ->where('id', $customerId)
            ->first(['phone', 'whatsapp_number']);

        if (! $user) {
            return false;
        }

        $candidates = $this->phoneMatchKeys([$user->phone, $user->whatsapp_number]);

        if (empty($candidates)) {
            return false;
        }

        return DB::table('whatsapp_interactions')
            ->where('vendor_id', $vendorId)
            ->where('event_type', WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value)
            ->where(function ($query) use ($candidates) {
                foreach ($candidates as $key) {
                    $query->orWhere('phone_number', 'like', '%'.$key);
                }
            })
            ->exists();
    }

    /**
     *
     * @param  array<int, string|null>  $phones
     * @return array<int, string>
     */
    private function phoneMatchKeys(array $phones): array
    {
        $keys = [];

        foreach ($phones as $phone) {
            if (! $phone) {
                continue;
            }

            $digits = preg_replace('/\D+/', '', $phone) ?? '';

            if (strlen($digits) < 9) {
                continue;
            }

            $keys[] = substr($digits, -10);
        }

        return array_values(array_unique($keys));
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
