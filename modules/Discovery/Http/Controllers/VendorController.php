<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Discovery\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Analytics\Services\AnalyticsService;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Discovery\Http\Requests\VendorRequest;
use ModulesShoppingComplex\Discovery\Services\VendorService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Repositories\UserRepository;
use ModulesShoppingComplex\Media\Services\MediaService;
use ModulesShoppingComplex\Reviews\Services\ReviewService;

class VendorController extends Controller
{
    public function __construct(
        private readonly VendorService $vendorService,
        private readonly ReviewService $reviewService,
        private readonly MediaService $mediaService,
        private readonly UserRepository $userRepository,
        private readonly AnalyticsService $analyticsService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function index(VendorRequest $request): Response
    {
        $filters = $request->getFilters();
        $vendors = $this->vendorService->getNearbyVendors($filters, perPage: 48);

        $vendorIds = $vendors->getCollection()->pluck('id')->all();
        $ratingStatsByVendor = $this->reviewService->getBulkVendorRatingStats($vendorIds);

        $transformedVendors = $vendors->through(function ($vendor) use ($ratingStatsByVendor) {
            $avatarMedia = $vendor->media->where('type', 'avatar')->first();
            $ratingStats = $ratingStatsByVendor[$vendor->id] ?? ['average' => 0.0, 'count' => 0];

            return [
                'id' => $vendor->id,
                'slug' => $vendor->slug,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'created_at' => $vendor->created_at->toISOString(),

                'role' => 'vendor',
                'business_name' => $vendor->business_name ?? $vendor->name,
                'business_description' => $vendor->bio,
                'business_logo' => $avatarMedia ? $this->mediaService->getMediaUrl($avatarMedia) : null,
                'rating' => $ratingStats['average'],
                'reviews_count' => $ratingStats['count'],
                'products_count' => $vendor->products_count ?? 0,
                'is_verified' => $vendor->isVendorVerified(),
                'is_online' => true,
                'whatsapp_number' => $vendor->whatsapp_number ?? null,

                'distance_km' => $vendor->distance_km !== null ? round((float) $vendor->distance_km, 2) : null,
                'distance_formatted' => $vendor->distance_km !== null ? $this->formatDistance((float) $vendor->distance_km) : null,
            ];
        });

        $categories = Cache::remember('vendor_listing_categories', 3600, fn () => Category::select('id', 'name', 'slug')
            ->withCount(['vendors'])
            ->having('vendors_count', '>', 0)
            ->orderBy('name')
            ->get()
        );

        return Inertia::render('Vendors/Index', [
            'vendors' => $transformedVendors,
            'filters' => $filters,
            'categories' => $categories,
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }

    public function show(string $vendorSlug): Response
    {
        $vendor = $this->findVendorBySlug($vendorSlug);
        $vendorId = $vendor->id;

        $products = Product::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->with(['media', 'vendor'])
            ->latest()
            ->paginate(50);

        $products->through(function ($product) {
            $product->images = $product->media->map(fn ($media) => [
                'id' => $media->id,
                'url' => $this->mediaService->getMediaUrl($media),
                'type' => $media->type,
                'is_primary' => true,
            ])->values()->all();

            return $product;
        });

        $ratingStats = $this->reviewService->getVendorRatingStats($vendorId);

        $avatarMedia = $vendor->media->where('type', 'avatar')->first();
        $bannerMedia = $vendor->media->where('type', 'banner')->first();

        $authUser = Auth::user();
        $isOwner = $authUser && $authUser->id === $vendor->id;
        $followersCount = $this->userRepository->getFollowersCount($vendorId);
        $isFollowing = $authUser && ! $isOwner
            ? $this->userRepository->isFollowing($authUser->id, $vendorId)
            : false;

        // Record profile view (skip if vendor viewing own profile)
        if (! $isOwner) {
            $this->analyticsService->recordProfileView($vendorId, $authUser?->id, request()->ip());
        }

        // Reviews
        $vendorReviews = $this->reviewService->getVendorReviews($vendorId, 5);
        $canReview = false;
        $hasReviewed = false;
        if ($authUser && $authUser->role === 'customer') {
            $canReview = $this->reviewService->canCustomerReviewVendor($authUser->id, $vendorId);
            $hasReviewed = $this->reviewService->hasReviewedVendor($authUser->id, $vendorId);
        }

        return Inertia::render('Vendor/Profile', [
            'vendor' => [
                'id' => $vendor->id,
                'slug' => $vendor->slug,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'business_name' => $vendor->business_name ?? $vendor->name,
                'business_description' => $vendor->bio,
                'business_logo' => $avatarMedia ? $this->mediaService->getMediaUrl($avatarMedia) : null,
                'banner_image' => $bannerMedia ? $this->mediaService->getMediaUrl($bannerMedia) : null,
                'is_verified' => $vendor->isVendorVerified(),
                'created_at' => $vendor->created_at->toISOString(),
                'whatsapp_number' => $vendor->whatsapp_number ?? null,
                'address' => $vendor->address?->street,
                'city' => $vendor->address?->city,
                'state' => $vendor->address?->state,
                'latitude' => $vendor->address?->latitude,
                'longitude' => $vendor->address?->longitude,
            ],
            'products' => $products,
            'stats' => [
                'products_count' => $vendor->active_products_count ?? 0,
                'reviews_count' => $ratingStats['count'],
                'average_rating' => $ratingStats['average'],
                'followers_count' => $followersCount,
                'plan_product_limit' => $isOwner
                    ? ($this->subscriptionService->getVendorSubscription($vendor->id)?->plan->product_limit ?? null)
                    : null,
            ],
            'vendor_reviews' => [
                'reviews' => $vendorReviews->items(),
                'meta' => [
                    'current_page' => $vendorReviews->currentPage(),
                    'last_page' => $vendorReviews->lastPage(),
                    'per_page' => $vendorReviews->perPage(),
                    'total' => $vendorReviews->total(),
                ],
            ],
            'can_review' => $canReview,
            'has_reviewed' => $hasReviewed,
            'isOwner' => $isOwner,
            'isFollowing' => $isFollowing,
        ]);
    }

    public function toggleFollow(string $vendorSlug): JsonResponse
    {
        $user = Auth::user();
        $vendor = $this->findVendorBySlug($vendorSlug);

        if ($user->id === $vendor->id) {
            return response()->json(['error' => 'You cannot follow yourself'], 400);
        }

        $result = $this->vendorService->toggleFollow($user->id, $vendor->id);

        return response()->json($result);
    }

    public function recordContact(string $vendorSlug): JsonResponse
    {
        $user = Auth::user();
        $vendor = $this->findVendorBySlug($vendorSlug);

        if ($user->id === $vendor->id) {
            return response()->json(['recorded' => false]);
        }

        $this->userRepository->recordVendorContact($user->id, $vendor->id);

        return response()->json(['recorded' => true]);
    }

    private function formatDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000).' m';
        }

        return round($distanceKm, 1).' km';
    }

    private function findVendorBySlug(string $slug): User
    {
        return User::where('slug', $slug)
            ->where('role', 'vendor')
            ->with(['media', 'vendorOnboarding', 'address'])
            ->withCount(['products as active_products_count' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();
    }
}
