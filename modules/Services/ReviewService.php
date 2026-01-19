<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Models\Review;
use ModulesShoppingComplex\Models\ReviewVote;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\ReviewRepository;

final readonly class ReviewService
{
    public function __construct(
        private ReviewRepository $reviewRepository
    ) {}

    /**
     * Get reviews for a vendor (public - only approved reviews).
     */
    public function getVendorReviews(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->reviewRepository->getForVendor(
            $vendorId,
            ReviewStatusEnum::APPROVED,
            $perPage,
            ['customer']
        );
    }

    /**
     * Get all reviews for a vendor (vendor dashboard - all statuses).
     */
    public function getVendorAllReviews(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->reviewRepository->getForVendor(
            $vendorId,
            null,
            $perPage,
            ['customer']
        );
    }

    /**
     * Get reviews pending moderation (admin only).
     */
    public function getPendingReviews(int $perPage = 20): LengthAwarePaginator
    {
        return $this->reviewRepository->getPendingModeration($perPage, ['customer', 'vendor']);
    }

    /**
     * Get reviews written by a customer.
     */
    public function getCustomerReviews(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->reviewRepository->getByCustomer($customerId, $perPage);
    }

    /**
     * Submit a new review.
     *
     * @throws \InvalidArgumentException
     */
    public function submitReview(
        User $customer,
        int $vendorId,
        int $rating,
        ?string $title = null,
        ?string $comment = null
    ): Review {
        // Prevent self-review
        if ($customer->id === $vendorId) {
            throw new \InvalidArgumentException('You cannot review yourself.');
        }

        // Get conversation in one query (also validates interaction)
        $conversation = DB::table('conversations')
            ->where('customer_id', $customer->id)
            ->where('vendor_id', $vendorId)
            ->first(['id']);

        if (! $conversation) {
            throw new \InvalidArgumentException('You must have interacted with this vendor before leaving a review.');
        }

        // Check for existing review
        if ($this->reviewRepository->findByCustomerAndVendor($customer->id, $vendorId)) {
            throw new \InvalidArgumentException('You have already reviewed this vendor.');
        }

        return $this->reviewRepository->create([
            'customer_id' => $customer->id,
            'vendor_id' => $vendorId,
            'conversation_id' => $conversation->id,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'status' => ReviewStatusEnum::PENDING,
        ]);
    }

    /**
     * Update an existing review.
     */
    public function updateReview(
        Review $review,
        int $rating,
        ?string $title = null,
        ?string $comment = null
    ): Review {
        return $this->reviewRepository->update($review, [
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'status' => ReviewStatusEnum::PENDING, // Re-submit for moderation
        ]);
    }

    /**
     * Delete a review.
     */
    public function deleteReview(Review $review): bool
    {
        return $this->reviewRepository->delete($review);
    }

    /**
     * Moderate a review (approve or reject).
     */
    public function moderateReview(Review $review, User $moderator, ReviewStatusEnum $status, ?string $notes = null): Review
    {
        return $this->reviewRepository->update($review, [
            'status' => $status,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
            'moderation_notes' => $notes,
        ]);
    }

    public function addVendorResponse(Review $review, string $response): Review
    {
        return $this->reviewRepository->update($review, [
            'vendor_response' => $response,
            'vendor_responded_at' => now(),
        ]);
    }

    /**
     * Vote on a review (helpful/not helpful).
     */
    public function voteOnReview(Review $review, User $user, bool $isHelpful): ReviewVote
    {
        return DB::transaction(function () use ($review, $user, $isHelpful) {
            $existingVote = $review->getUserVote($user->id);

            if ($existingVote) {
                // Update existing vote if different
                if ($existingVote->is_helpful !== $isHelpful) {
                    // Adjust counts
                    if ($existingVote->is_helpful) {
                        $this->reviewRepository->decrementHelpfulCount($review->id);
                        $this->reviewRepository->incrementNotHelpfulCount($review->id);
                    } else {
                        $this->reviewRepository->decrementNotHelpfulCount($review->id);
                        $this->reviewRepository->incrementHelpfulCount($review->id);
                    }

                    $existingVote->update(['is_helpful' => $isHelpful]);
                }

                return $existingVote;
            }

            // Create new vote
            $vote = ReviewVote::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'is_helpful' => $isHelpful,
            ]);

            // Update counts
            if ($isHelpful) {
                $this->reviewRepository->incrementHelpfulCount($review->id);
            } else {
                $this->reviewRepository->incrementNotHelpfulCount($review->id);
            }

            return $vote;
        });
    }

    /**
     * Remove a vote from a review.
     */
    public function removeVote(Review $review, User $user): bool
    {
        return DB::transaction(function () use ($review, $user) {
            $vote = $review->getUserVote($user->id);

            if (! $vote) {
                return false;
            }

            // Adjust counts
            if ($vote->is_helpful) {
                $this->reviewRepository->decrementHelpfulCount($review->id);
            } else {
                $this->reviewRepository->decrementNotHelpfulCount($review->id);
            }

            return $vote->delete() ?? false;
        });
    }

    /**
     * Get vendor's rating statistics.
     *
     * @return array{average: float, count: int, distribution: array<int, int>}
     */
    public function getVendorRatingStats(int $vendorId): array
    {
        $stats = $this->reviewRepository->getVendorRatingStats($vendorId);
        $distribution = $this->reviewRepository->getVendorRatingDistribution($vendorId);

        return [
            'average' => $stats['average'],
            'count' => $stats['count'],
            'distribution' => $distribution,
        ];
    }

    /**
     * Check if customer can review a vendor.
     */
    public function canCustomerReviewVendor(int $customerId, int $vendorId): bool
    {
        // Must have interacted with vendor
        if (! $this->reviewRepository->hasCustomerInteractedWithVendor($customerId, $vendorId)) {
            return false;
        }

        // Must not have already reviewed
        if ($this->reviewRepository->findByCustomerAndVendor($customerId, $vendorId)) {
            return false;
        }

        return true;
    }

    /**
     * Get a review by ID.
     */
    public function getReview(int $id): ?Review
    {
        return $this->reviewRepository->find($id, ['customer', 'vendor', 'conversation']);
    }

    /**
     * Check if user has already reviewed vendor.
     */
    public function hasReviewedVendor(int $customerId, int $vendorId): bool
    {
        return $this->reviewRepository->findByCustomerAndVendor($customerId, $vendorId) !== null;
    }

    /**
     * Check if a vendor exists.
     */
    public function vendorExists(int $vendorId): bool
    {
        return $this->reviewRepository->vendorExists($vendorId);
    }
}
