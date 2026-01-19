<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Http\Requests\ModerateReviewRequest;
use ModulesShoppingComplex\Http\Requests\StoreReviewRequest;
use ModulesShoppingComplex\Http\Requests\UpdateReviewRequest;
use ModulesShoppingComplex\Http\Requests\VendorResponseRequest;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Models\Enums\UserEnum;
use ModulesShoppingComplex\Models\Review;
use ModulesShoppingComplex\Services\ReviewService;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * GET /vendors/{vendorId}/reviews
     * Get public reviews for a vendor.
     */
    public function index(int $vendorId, Request $request): JsonResponse
    {
        if (! $this->reviewService->vendorExists($vendorId)) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        $perPage = $this->getPerPage($request);
        $reviews = $this->reviewService->getVendorReviews($vendorId, $perPage);

        return $this->paginatedResponse($reviews, 'reviews');
    }

    /**
     * GET /vendors/{vendorId}/reviews/stats
     * Get rating statistics for a vendor.
     */
    public function stats(int $vendorId): JsonResponse
    {
        if (! $this->reviewService->vendorExists($vendorId)) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        $stats = $this->reviewService->getVendorRatingStats($vendorId);

        return response()->json($stats);
    }

    /**
     * GET /vendors/{vendorId}/reviews/can-review
     * Check if current user can review the vendor.
     */
    public function canReview(int $vendorId, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== UserEnum::CUSTOMER->value) {
            return response()->json([
                'can_review' => false,
                'reason' => 'Only customers can leave reviews.',
            ]);
        }

        $canReview = $this->reviewService->canCustomerReviewVendor($user->id, $vendorId);
        $hasReviewed = $this->reviewService->hasReviewedVendor($user->id, $vendorId);

        $reason = null;
        if (! $canReview) {
            $reason = $hasReviewed
                ? 'You have already reviewed this vendor.'
                : 'You must interact with this vendor before leaving a review.';
        }

        return response()->json([
            'can_review' => $canReview,
            'has_reviewed' => $hasReviewed,
            'reason' => $reason,
        ]);
    }

    /**
     * POST /reviews
     * Submit a new review.
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $review = $this->reviewService->submitReview(
                $request->user(),
                $validated['vendor_id'],
                $validated['rating'],
                $validated['title'] ?? null,
                $validated['comment'] ?? null
            );

            return response()->json([
                'review' => $review->load('vendor'),
                'message' => 'Review submitted successfully. It will be visible after moderation.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /reviews/{review}
     * Get a specific review.
     */
    public function show(Review $review, Request $request): JsonResponse
    {
        $this->authorize('view', $review);

        $review->load(['customer', 'vendor']);

        $userVote = null;
        if ($user = $request->user()) {
            $vote = $review->getUserVote($user->id);
            $userVote = $vote ? $vote->is_helpful : null;
        }

        return response()->json([
            'review' => $review,
            'user_vote' => $userVote,
        ]);
    }

    /**
     * PUT /reviews/{review}
     * Update an existing review.
     */
    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $validated = $request->validated();

        $review = $this->reviewService->updateReview(
            $review,
            $validated['rating'],
            $validated['title'] ?? null,
            $validated['comment'] ?? null
        );

        return response()->json([
            'review' => $review,
            'message' => 'Review updated successfully. It will be visible after moderation.',
        ]);
    }

    /**
     * DELETE /reviews/{review}
     * Delete a review.
     */
    public function destroy(Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $this->reviewService->deleteReview($review);

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }

    /**
     * POST /reviews/{review}/vote
     * Vote on a review (helpful/not helpful).
     */
    public function vote(Review $review, Request $request): JsonResponse
    {
        $this->authorize('vote', $review);

        $request->validate([
            'is_helpful' => 'required|boolean',
        ]);

        $vote = $this->reviewService->voteOnReview(
            $review,
            $request->user(),
            $request->boolean('is_helpful')
        );

        $review->refresh();

        return response()->json([
            'vote' => $vote,
            'helpful_count' => $review->helpful_count,
            'not_helpful_count' => $review->not_helpful_count,
            'message' => 'Vote recorded successfully.',
        ]);
    }

    /**
     * DELETE /reviews/{review}/vote
     * Remove vote from a review.
     */
    public function removeVote(Review $review, Request $request): JsonResponse
    {
        $this->authorize('vote', $review);

        $removed = $this->reviewService->removeVote($review, $request->user());

        $review->refresh();

        return response()->json([
            'removed' => $removed,
            'helpful_count' => $review->helpful_count,
            'not_helpful_count' => $review->not_helpful_count,
            'message' => $removed ? 'Vote removed successfully.' : 'No vote to remove.',
        ]);
    }

    /**
     * POST /reviews/{review}/respond
     * Add vendor response to a review.
     */
    public function respond(VendorResponseRequest $request, Review $review): JsonResponse
    {
        $this->authorize('respond', $review);

        $validated = $request->validated();

        $review = $this->reviewService->addVendorResponse($review, $validated['response']);

        return response()->json([
            'review' => $review,
            'message' => 'Response added successfully.',
        ]);
    }

    /**
     * GET /my-reviews
     * Get reviews written by the current user.
     */
    public function myReviews(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reviews = $this->reviewService->getCustomerReviews($request->user()->id, $perPage);

        return $this->paginatedResponse($reviews, 'reviews');
    }

    /**
     * GET /admin/reviews/pending
     * Get reviews pending moderation (admin only).
     */
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('moderate', Review::class);

        $perPage = $this->getPerPage($request, 20);
        $reviews = $this->reviewService->getPendingReviews($perPage);

        return $this->paginatedResponse($reviews, 'reviews');
    }

    /**
     * POST /admin/reviews/{review}/moderate
     * Moderate a review (approve/reject).
     */
    public function moderate(ModerateReviewRequest $request, Review $review): JsonResponse
    {
        $validated = $request->validated();

        $status = $validated['action'] === 'approve'
            ? ReviewStatusEnum::APPROVED
            : ReviewStatusEnum::REJECTED;

        $review = $this->reviewService->moderateReview(
            $review,
            $request->user(),
            $status,
            $validated['notes'] ?? null
        );

        $message = $validated['action'] === 'approve'
            ? 'Review approved successfully.'
            : 'Review rejected successfully.';

        return response()->json([
            'review' => $review,
            'message' => $message,
        ]);
    }

    /**
     * GET /vendor/reviews
     * Get all reviews for the authenticated vendor.
     */
    public function vendorReviews(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserEnum::VENDOR->value) {
            return response()->json([
                'message' => 'Only vendors can access this endpoint.',
            ], 403);
        }

        $perPage = $this->getPerPage($request);
        $reviews = $this->reviewService->getVendorAllReviews($user->id, $perPage);

        return $this->paginatedResponse($reviews, 'reviews');
    }

    /**
     * Generate a paginated JSON response.
     */
    private function paginatedResponse(LengthAwarePaginator $paginator, string $key = 'data'): JsonResponse
    {
        return response()->json([
            $key => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Get per_page value from request with max limit.
     */
    private function getPerPage(Request $request, int $default = 15): int
    {
        return min((int) $request->get('per_page', $default), 50);
    }
}
