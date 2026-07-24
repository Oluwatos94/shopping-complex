<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ModulesShoppingComplex\Http\Requests\VendorResponseRequest;
use ModulesShoppingComplex\Identity\Enums\UserEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Reviews\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Reviews\Http\Requests\ModerateReviewRequest;
use ModulesShoppingComplex\Reviews\Http\Requests\StoreReviewRequest;
use ModulesShoppingComplex\Reviews\Http\Requests\UpdateReviewRequest;
use ModulesShoppingComplex\Reviews\Models\Review;
use ModulesShoppingComplex\Reviews\Services\ReviewService;
use ModulesShoppingComplex\Shared\Http\Concerns\PaginatesResults;

class ReviewController extends Controller
{
    use PaginatesResults;

    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    public function index(string $vendorSlug, Request $request): JsonResponse
    {
        $vendor = User::where('slug', $vendorSlug)->where('role', 'vendor')->first();

        if (! $vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        $perPage = $this->getPerPage($request);
        $reviews = $this->reviewService->getVendorReviews($vendor->id, $perPage);

        return $this->paginatedResponse($reviews, 'reviews');
    }

    public function stats(string $vendorSlug): JsonResponse
    {
        $vendor = User::where('slug', $vendorSlug)->where('role', 'vendor')->first();

        if (! $vendor) {
            return response()->json(['message' => 'Vendor not found.'], 404);
        }

        $stats = $this->reviewService->getVendorRatingStats($vendor->id);

        return response()->json($stats);
    }

    public function canReview(string $vendorSlug, Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== UserEnum::CUSTOMER->value) {
            return response()->json([
                'can_review' => false,
                'reason' => 'Only customers can leave reviews.',
            ]);
        }

        $vendor = User::where('slug', $vendorSlug)->where('role', 'vendor')->firstOrFail();
        $canReview = $this->reviewService->canCustomerReviewVendor($user->id, $vendor->id);
        $hasReviewed = $this->reviewService->hasReviewedVendor($user->id, $vendor->id);

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
                'message' => 'Review submitted successfully.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

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
            'message' => 'Review updated successfully.',
        ]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $this->authorize('delete', $review);

        $this->reviewService->deleteReview($review);

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }

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

    public function myReviews(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $reviews = $this->reviewService->getCustomerReviews($request->user()->id, $perPage);

        return $this->paginatedResponse($reviews, 'reviews');
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('moderate', Review::class);

        $perPage = $this->getPerPage($request, 20);
        $reviews = $this->reviewService->getPendingReviews($perPage);

        return $this->paginatedResponse($reviews, 'reviews');
    }

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
}
