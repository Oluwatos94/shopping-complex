<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Reviews\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Services\NotificationService;
use ModulesShoppingComplex\Reviews\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Reviews\Events\ReviewReceivedEvent;
use ModulesShoppingComplex\Reviews\Models\Review;
use ModulesShoppingComplex\Reviews\Models\ReviewVote;
use ModulesShoppingComplex\Reviews\Repositories\ReviewRepository;

final readonly class ReviewService
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private NotificationService $notificationService
    ) {}

    public function getVendorReviews(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->reviewRepository->getForVendor(
            $vendorId,
            ReviewStatusEnum::APPROVED,
            $perPage,
            ['customer']
        );
    }

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
        if ($customer->id === $vendorId) {
            throw new InvalidArgumentException('You cannot review yourself.');
        }

        $review = DB::transaction(function () use ($customer, $vendorId, $rating, $title, $comment) {
            $conversation = DB::table('conversations')
                ->where('customer_id', $customer->id)
                ->where('vendor_id', $vendorId)
                ->first(['id']);

            if (! $conversation
                && ! $this->reviewRepository->hasWebContactWithVendor($customer->id, $vendorId)
                && ! $this->reviewRepository->hasWhatsAppContactWithVendor($customer->id, $vendorId)) {
                throw new InvalidArgumentException('You must have interacted with this vendor before leaving a review.');
            }

            $existingReview = Review::withTrashed()
                ->where('customer_id', $customer->id)
                ->where('vendor_id', $vendorId)
                ->lockForUpdate()
                ->first();

            if ($existingReview) {
                throw new InvalidArgumentException('You have already reviewed this vendor.');
            }

            return $this->reviewRepository->create([
                'customer_id' => $customer->id,
                'vendor_id' => $vendorId,
                'conversation_id' => $conversation?->id,
                'rating' => $rating,
                'title' => $title,
                'comment' => $comment,
                'status' => ReviewStatusEnum::APPROVED,
            ]);
        });

        $this->notifyVendorOfReview($customer, $vendorId, $review);

        return $review;
    }

    private function notifyVendorOfReview(User $customer, int $vendorId, Review $review): void
    {
        $vendor = User::find($vendorId);

        if (! $vendor) {
            return;
        }

        $this->notificationService->send(
            new ReviewReceivedEvent($vendor, $customer, $review->rating, $review->title)
        );
    }

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
            'status' => ReviewStatusEnum::APPROVED,
        ]);
    }

    public function deleteReview(Review $review): bool
    {
        return $this->reviewRepository->delete($review);
    }

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

    public function voteOnReview(Review $review, User $user, bool $isHelpful): ReviewVote
    {
        return DB::transaction(function () use ($review, $user, $isHelpful) {
            Review::where('id', $review->id)->lockForUpdate()->first();

            $existingVote = ReviewVote::where('review_id', $review->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existingVote) {
                if ($existingVote->is_helpful !== $isHelpful) {
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

            $vote = ReviewVote::create([
                'review_id' => $review->id,
                'user_id' => $user->id,
                'is_helpful' => $isHelpful,
            ]);

            if ($isHelpful) {
                $this->reviewRepository->incrementHelpfulCount($review->id);
            } else {
                $this->reviewRepository->incrementNotHelpfulCount($review->id);
            }

            return $vote;
        });
    }

    public function removeVote(Review $review, User $user): bool
    {
        return DB::transaction(function () use ($review, $user) {
            Review::where('id', $review->id)->lockForUpdate()->first();

            $vote = ReviewVote::where('review_id', $review->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $vote) {
                return false;
            }

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
        return $this->reviewRepository->getVendorRatingStatsWithDistribution($vendorId);
    }

    public function getBulkVendorRatingStats(array $vendorIds): array
    {
        return $this->reviewRepository->getBulkVendorRatingStats($vendorIds);
    }

    public function canCustomerReviewVendor(int $customerId, int $vendorId): bool
    {
        if (! $this->reviewRepository->hasCustomerInteractedWithVendor($customerId, $vendorId)) {
            return false;
        }

        // Must not have already reviewed
        if ($this->reviewRepository->findByCustomerAndVendor($customerId, $vendorId)) {
            return false;
        }

        return true;
    }

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

    public function vendorExists(int $vendorId): bool
    {
        return $this->reviewRepository->vendorExists($vendorId);
    }
}
