<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use ModulesShoppingComplex\Http\Requests\SaveOnboardingRequest;
use ModulesShoppingComplex\Http\Requests\SubmitOnboardingRequest;
use ModulesShoppingComplex\Http\Requests\UpdateVendorProfileRequest;
use ModulesShoppingComplex\Http\Requests\UploadProductRequest;
use ModulesShoppingComplex\Http\Requests\VendorRegisterRequest;
use ModulesShoppingComplex\Http\Requests\VendorRequest;
use ModulesShoppingComplex\Models\Address;
use ModulesShoppingComplex\Models\Category;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\UserRepository;
use ModulesShoppingComplex\Services\AnalyticsService;
use ModulesShoppingComplex\Services\MediaService;
use ModulesShoppingComplex\Services\ReviewService;
use ModulesShoppingComplex\Services\SubscriptionService;
use ModulesShoppingComplex\Services\VendorService;

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
        $vendors = $this->vendorService->getNearbyVendors($filters, perPage: 100);

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

    /**
     * Generate a base64 data URL for a private document file.
     */
    private function getSecureDocumentUrl(string $path): ?string
    {
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mimeType = mime_content_type(Storage::disk('local')->path($path)) ?: 'application/octet-stream';
        $content = Storage::disk('local')->get($path);

        return 'data:'.$mimeType.';base64,'.base64_encode($content);
    }

    private function formatDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000).' m';
        }

        return round($distanceKm, 1).' km';
    }

    public function dashboard(): Response
    {
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            return Inertia::render('index');
        }

        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $subscription = $this->subscriptionService->getVendorSubscription($user->id);
        $isFree = $subscription?->plan->isFree() ?? false;

        $daysRemaining = null;
        if ($subscription !== null && ! $isFree && $subscription->expires_at) {
            $daysRemaining = max(0, (int) now()->diffInDays($subscription->expires_at, false));
        }

        $profileViewMetrics = $this->analyticsService->getProfileViewMetrics($user->id, $startOfWeek, $endOfWeek);
        $chatContactMetrics = $this->analyticsService->getChatContactMetrics($user->id, $startOfWeek, $endOfWeek);
        $activeProductsCount = $user->products()->where('is_active', true)->count();

        return Inertia::render('Vendor/Dashboard', [
            'vendor' => [
                'name' => $user->name,
                'business_name' => $user->business_name ?? $user->name,
                'slug' => $user->slug,
            ],
            'subscription' => [
                'plan_name' => $subscription?->plan->name ?? null,
                'plan_slug' => $subscription?->plan->slug ?? null,
                'expires_at' => ($subscription !== null && ! $isFree) ? $subscription->expires_at->toDateString() : null,
                'days_remaining' => $daysRemaining,
                'is_expired' => $subscription !== null && $subscription->status === 'expired',
                'product_limit' => $subscription?->plan->product_limit ?? null,
            ],
            'stats' => [
                'active_products' => $activeProductsCount,
                'catalogue_views_this_week' => $profileViewMetrics['total'],
                'contact_requests_this_week' => $chatContactMetrics['total'],
            ],
        ]);
    }

    public function vendorProducts(): Response
    {
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            return Inertia::render('index');
        }

        $products = Product::where('vendor_id', $user->id)
            ->with('media')
            ->latest()
            ->paginate(20);

        $products->through(function ($product) {
            $primaryMedia = $product->media->first();

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_active' => $product->is_active,
                'created_at' => $product->created_at->toDateString(),
                'image' => $primaryMedia ? $this->mediaService->getMediaUrl($primaryMedia) : null,
                'image_type' => $primaryMedia?->type,
            ];
        });

        return Inertia::render('Vendor/Products', [
            'products' => $products,
            'vendor_slug' => $user->slug,
        ]);
    }

    public function register(): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user->role === 'vendor') {
            return redirect()->route('vendor.show', $user->slug);
        }

        $categories = Cache::remember('vendor_register_categories', 3600, fn () => Category::select('id', 'name', 'slug')->orderBy('name')->get()
        );

        return Inertia::render('Vendor/Register', [
            'categories' => $categories,
        ]);
    }

    public function storeRegistration(VendorRegisterRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $vendor = $this->vendorService->registerAsVendor(
            $user,
            $request->only(['business_name', 'bio', 'category_id', 'whatsapp_number', 'address', 'city', 'state', 'latitude', 'longitude']),
            $request->file('avatar')
        );

        return redirect()->route('vendor.show', $vendor->slug)
            ->with('success', 'Welcome! Your vendor profile has been created.');
    }

    public function show(string $vendorSlug): Response
    {
        $vendor = $this->findVendorBySlug($vendorSlug);
        $vendorId = $vendor->id;

        $products = Product::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->with(['media', 'vendor'])
            ->latest()
            ->paginate(12);

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

    public function uploadProduct(UploadProductRequest $request): RedirectResponse
    {
        // TODO: Re-enable after admin panel is built
        // $this->authorize('create', Product::class);

        $user = Auth::user();

        $subscription = $this->subscriptionService->getVendorSubscription($user->id);
        if ($subscription !== null) {
            $activeCount = $user->products()->where('is_active', true)->count();
            if ($activeCount >= $subscription->plan->product_limit) {
                return redirect()->back()->withErrors([
                    'message' => "You have reached the product limit for your {$subscription->plan->name} plan. Upgrade to add more products.",
                ]);
            }
        }

        $product = DB::transaction(function () use ($user, $request) {
            $product = Product::create([
                'name' => $request->input('name'),
                'slug' => Str::slug($request->input('name')).'-'.uniqid(),
                'description' => $request->input('description'),
                'price' => $request->input('price'),
                'vendor_id' => $user->id,
                'category_id' => $user->category_id,
                'stock' => 0,
                'is_active' => true,
                'pay_on_delivery' => $request->boolean('pay_on_delivery'),
                'is_returnable' => $request->boolean('is_returnable'),
                'tags' => array_values(array_filter(array_map('strtolower', $request->input('tags', [])))),
            ]);

            if ($request->hasFile('video')) {
                $this->mediaService->uploadVideo(
                    file: $request->file('video'),
                    modelType: Product::class,
                    modelId: $product->id,
                    type: 'product_video'
                );
            } elseif ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $this->mediaService->uploadImage(
                        file: $imageFile,
                        modelType: Product::class,
                        modelId: $product->id,
                        type: 'product_image'
                    );
                }
            }

            return $product;
        });

        return redirect()->back()->with('success', 'Product uploaded successfully.');
    }

    public function updateProduct(UploadProductRequest $request, int $productId): RedirectResponse
    {
        $user = Auth::user();
        $product = Product::where('id', $productId)->where('vendor_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($request, $product) {
            $product->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'price' => $request->input('price'),
                'pay_on_delivery' => $request->boolean('pay_on_delivery'),
                'is_returnable' => $request->boolean('is_returnable'),
                'tags' => array_values(array_filter(array_map('strtolower', $request->input('tags', [])))),
            ]);

            if ($request->hasFile('video')) {
                $this->mediaService->deleteMediaForModel(Product::class, $product->id);
                $this->mediaService->uploadVideo(
                    file: $request->file('video'),
                    modelType: Product::class,
                    modelId: $product->id,
                    type: 'product_video'
                );
            } elseif ($request->hasFile('images')) {
                $this->mediaService->deleteMediaForModel(Product::class, $product->id);
                foreach ($request->file('images') as $imageFile) {
                    $this->mediaService->uploadImage(
                        file: $imageFile,
                        modelType: Product::class,
                        modelId: $product->id,
                        type: 'product_image'
                    );
                }
            }
        });

        return redirect()->back()->with('success', 'Product updated successfully.');
    }

    public function deleteProduct(int $productId): RedirectResponse
    {
        $user = Auth::user();
        $product = Product::where('id', $productId)->where('vendor_id', $user->id)->firstOrFail();

        DB::transaction(function () use ($product) {
            $this->mediaService->deleteMediaForModel(Product::class, $product->id);
            $product->delete();
        });

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }

    public function updateProfile(UpdateVendorProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();

        DB::transaction(function () use ($user, $request) {
            $user->update([
                'business_name' => $request->input('business_name'),
                'bio' => $request->input('bio'),
                'whatsapp_number' => $request->input('whatsapp_number'),
            ]);

            Address::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'street' => $request->input('address'),
                    'city' => $request->input('city'),
                    'state' => $request->input('state'),
                    'country' => 'Nigeria',
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                ]
            );

            if ($request->hasFile('avatar')) {
                $this->mediaService->deleteMediaByType(User::class, $user->id, 'avatar');
                $this->mediaService->uploadImage(
                    file: $request->file('avatar'),
                    modelType: User::class,
                    modelId: $user->id,
                    type: 'avatar'
                );
            }

            if ($request->hasFile('banner')) {
                $this->mediaService->deleteMediaByType(User::class, $user->id, 'banner');
                $this->mediaService->uploadImage(
                    file: $request->file('banner'),
                    modelType: User::class,
                    modelId: $user->id,
                    type: 'banner'
                );
            }
        });

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    private function findVendorBySlug(string $slug): User
    {
        return User::where('slug', $slug)
            ->where('role', 'vendor')
            ->with(['media', 'vendorOnboarding', 'address'])
            ->withCount(['products as active_products_count' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();
    }

    /**
     * Show vendor onboarding form.
     */
    public function onboarding(): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can access the verification process.');
        }

        $onboarding = $this->vendorService->getOnboarding($user->id);

        if ($onboarding?->isApproved()) {
            return redirect()->route('products.index')
                ->with('info', 'Your vendor account is already verified.');
        }

        if ($onboarding?->isPendingReview()) {
            return redirect()->route('vendor.onboarding.success');
        }

        $categories = Cache::remember('vendor_onboarding_categories', 3600, fn () => Category::select('id', 'name', 'slug')->orderBy('name')->get()
        );

        $savedData = null;
        if ($onboarding) {
            $savedData = [
                'business_info' => [
                    'legal_entity_name' => $onboarding->legal_entity_name ?? '',
                    'business_category' => $onboarding->business_category ?? '',
                    'tax_identification_number' => $onboarding->tax_identification_number ?? '',
                    'physical_address' => $onboarding->physical_address ?? '',
                    'whatsapp_number' => $user->whatsapp_number ?? '',
                ],
                'verification' => [
                    'certificate_of_incorporation' => null,
                    'government_issued_id' => null,
                    'proof_of_address' => null,
                    'certificate_of_incorporation_preview' => $onboarding->certificate_of_incorporation
                        ? $this->getSecureDocumentUrl($onboarding->certificate_of_incorporation)
                        : null,
                    'government_issued_id_preview' => $onboarding->government_issued_id
                        ? $this->getSecureDocumentUrl($onboarding->government_issued_id)
                        : null,
                    'proof_of_address_preview' => $onboarding->proof_of_address
                        ? $this->getSecureDocumentUrl($onboarding->proof_of_address)
                        : null,
                ],
                'bank_details' => [
                    'bank_name' => $onboarding->bank_name ?? '',
                    'bank_branch' => $onboarding->bank_branch ?? '',
                    'account_number' => $onboarding->account_number ?? '',
                    'swift_bic_code' => $onboarding->swift_bic_code ?? '',
                ],
                'agreed_to_terms' => $onboarding->agreed_to_terms,
                'current_step' => $onboarding->current_step,
            ];
        }

        return Inertia::render('Vendor/Onboarding', [
            'categories' => $categories,
            'savedData' => $savedData,
        ]);
    }

    public function saveOnboarding(SaveOnboardingRequest $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $this->vendorService->saveOnboardingDraft(
            $user,
            $request->getBusinessInfo(),
            $request->getBankDetails(),
            (int) $request->input('current_step', 1),
            $request->getUploadedFiles()
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Progress saved']);
        }

        return redirect()->route('home')->with('success', 'Your progress has been saved.');
    }

    public function submitOnboarding(SubmitOnboardingRequest $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        try {
            $this->vendorService->submitOnboarding(
                $user,
                $request->getBusinessInfo(),
                $request->getBankDetails(),
                $request->boolean('agreed_to_terms'),
                $request->getUploadedFiles()
            );
        } catch (InvalidArgumentException $e) {
            $errors = json_decode($e->getMessage(), true);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $errors], 422);
            }

            return back()->withErrors($errors);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Application submitted successfully']);
        }

        return redirect()->route('vendor.onboarding.success')
            ->with('success', 'Your vendor application has been submitted for review.');
    }

    public function onboardingSuccess(): Response
    {
        return Inertia::render('Vendor/OnboardingSuccess');
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
}
