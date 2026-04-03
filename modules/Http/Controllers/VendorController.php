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
use ModulesShoppingComplex\Http\Requests\UploadProductRequest;
use ModulesShoppingComplex\Http\Requests\VendorRegisterRequest;
use ModulesShoppingComplex\Http\Requests\VendorRequest;
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
        $vendors = $this->vendorService->getNearbyVendors($filters, perPage: 12);

        $transformedVendors = $vendors->through(function ($vendor) {
            $profileImage = $vendor->media->first()?->file_path;

            return [
                // BaseUser fields
                'id' => $vendor->id,
                'slug' => $vendor->slug,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'email_verified_at' => $vendor->email_verified_at?->toISOString(),
                'created_at' => $vendor->created_at->toISOString(),
                'updated_at' => $vendor->updated_at->toISOString(),

                // Vendor-specific fields
                'role' => 'vendor',
                'business_name' => $vendor->business_name ?? $vendor->name,
                'business_description' => $vendor->bio,
                'business_logo' => $profileImage,
                'rating' => 4.5, // Placeholder - will come from reviews table
                'total_sales' => 0, // Placeholder - not tracking sales yet
                'products_count' => $vendor->products_count ?? 0,
                'is_verified' => $vendor->email_verified_at !== null,
                'is_online' => true, // Placeholder - will be real-time WebSocket status

                // NearbyVendor fields
                'distance_km' => round($vendor->distance_km ?? 0, 2),
                'distance_formatted' => $this->formatDistance($vendor->distance_km ?? 0),
                'response_time_minutes' => 15, // Placeholder
                'avg_response_time' => 15, // Placeholder
                'reviews_count' => 0, // Placeholder

                // Optional location (when GPS fields are added)
                'location' => null, // Will be populated when GPS fields exist
            ];
        });

        return Inertia::render('Vendors/Index', [
            'vendors' => $transformedVendors,
            'filters' => $filters,
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

    /**
     * Format distance for display
     */
    private function formatDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000).' m';
        }

        return round($distanceKm, 1).' km';
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
            $request->only(['business_name', 'bio', 'category_id']),
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
                'is_primary' => true,
            ])->values()->all();

            return $product;
        });

        $ratingStats = $this->reviewService->getVendorRatingStats($vendorId);

        $avatarMedia = $vendor->media->where('type', 'avatar')->first();

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

        return Inertia::render('Vendor/Profile', [
            'vendor' => [
                'id' => $vendor->id,
                'slug' => $vendor->slug,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'business_name' => $vendor->business_name ?? $vendor->name,
                'business_description' => $vendor->bio,
                'business_logo' => $avatarMedia ? $this->mediaService->getMediaUrl($avatarMedia) : null,
                'is_verified' => $vendor->isVendorVerified(),
                'created_at' => $vendor->created_at->toISOString(),
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
            'isOwner' => $isOwner,
            'isFollowing' => $isFollowing,
        ]);
    }

    public function uploadProduct(UploadProductRequest $request): JsonResponse
    {
        // TODO: Re-enable after admin panel is built
        // $this->authorize('create', Product::class);

        $user = Auth::user();

        $product = DB::transaction(function () use ($user, $request) {
            $product = Product::create([
                'name' => $request->input('name'),
                'slug' => Str::slug($request->input('name')).'-'.uniqid(),
                'description' => $request->input('name', ''),
                'price' => $request->input('price'),
                'vendor_id' => $user->id,
                'category_id' => $user->category_id,
                'stock' => 0,
                'is_active' => true,
            ]);

            $this->mediaService->uploadImage(
                file: $request->file('image'),
                modelType: Product::class,
                modelId: $product->id,
                type: 'product_image'
            );

            return $product;
        });

        return response()->json([
            'success' => true,
            'message' => 'Product uploaded successfully.',
            'product_id' => $product->id,
        ]);
    }

    private function findVendorBySlug(string $slug): User
    {
        return User::where('slug', $slug)
            ->where('role', 'vendor')
            ->with(['media', 'vendorOnboarding'])
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
}
