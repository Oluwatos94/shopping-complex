<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\ImageUploadRequest;
use ModulesShoppingComplex\Http\Requests\ProductFormRequest;
use ModulesShoppingComplex\Models\Category;
use ModulesShoppingComplex\Models\Media;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Services\AnalyticsService;
use ModulesShoppingComplex\Services\MediaService;
use ModulesShoppingComplex\Services\ProductService;
use ModulesShoppingComplex\Services\ReviewService;
use ModulesShoppingComplex\Services\SubscriptionService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MediaService $mediaService,
        private readonly ReviewService $reviewService,
        private readonly AnalyticsService $analyticsService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function index(): Response
    {
        $locationFilters = request()->only(['latitude', 'longitude', 'radius']);
        $userLat = isset($locationFilters['latitude']) ? (float) $locationFilters['latitude'] : null;
        $userLon = isset($locationFilters['longitude']) ? (float) $locationFilters['longitude'] : null;

        $products = $this->productService->index(perPage: 100, locationFilters: $locationFilters);
        $categories = Cache::remember('product_index_categories', 3600, fn () => Category::withCount('products')->get()
        );

        // Eager-load vendor address so we can compute distance per product
        /** @var \Illuminate\Database\Eloquent\Collection<int, \ModulesShoppingComplex\Models\Product> $collection */
        $collection = $products->getCollection();
        $collection->loadMissing('vendor.address');

        $products->through(function ($product) use ($userLat, $userLon) {
            $product->images = $product->media->map(fn ($media) => [
                'id' => $media->id,
                'url' => $this->mediaService->getMediaUrl($media),
                'type' => $media->type,
                'is_primary' => true,
            ])->values()->all();

            if ($userLat && $userLon) {
                $address = $product->vendor?->address;
                if ($address !== null && $address->latitude && $address->longitude) {
                    $dist = $this->haversineKm($userLat, $userLon, (float) $address->latitude, (float) $address->longitude);
                    $product->distance_formatted = $dist < 1
                        ? round($dist * 1000).' m away'
                        : round($dist, 1).' km away';
                }
            }

            return $product;
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Products/Create');
    }

    public function store(ProductFormRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Product::class);

        if ($error = $this->checkProductLimit()) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $error], 422);
            }

            return back()->with('error', $error);
        }

        $validated = $request->validated();
        $validated['vendor_id'] = Auth::id();

        $product = $this->productService->createProduct($validated);

        return redirect()->route('products.show', $product->slug)->with('success', 'Product created successfully.');
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product = $this->productService->getProduct($product->id);
        $vendor = $product->vendor;

        // Record product view (skip if vendor viewing own product)
        $authUser = Auth::user();
        if (! $authUser || $authUser->id !== $vendor->id) {
            $this->analyticsService->recordProductView($product->id, $vendor->id, $authUser?->id, request()->ip());
        }

        // Load vendor relationships and counts in one go (media already loaded via getProduct)
        if (! $vendor->relationLoaded('media')) {
            $vendor->load('media');
        }
        $vendor->loadCount('products');

        $vendorStats = $this->reviewService->getVendorRatingStats($vendor->id);

        // Get related products from the same category (excluding current product)
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['vendor', 'media'])
            ->limit(8)
            ->get();

        // Transform product images for frontend
        $productData = $product->toArray();
        $productData['images'] = $product->media->map(fn ($media) => [
            'id' => $media->id,
            'url' => $this->mediaService->getMediaUrl($media),
            'type' => $media->type,
        ])->values()->all();

        // Transform vendor data
        $avatarMedia = $vendor->media->where('type', 'avatar')->first();
        $vendorData = [
            'id' => $vendor->id,
            'slug' => $vendor->slug,
            'name' => $vendor->name,
            'role' => 'vendor',
            'business_name' => $vendor->business_name ?? $vendor->name,
            'business_description' => $vendor->bio,
            'business_logo' => $avatarMedia ? $this->mediaService->getMediaUrl($avatarMedia) : null,
            'available_hours' => $vendor->available_hours,
            'rating' => $vendorStats['average'],
            'products_count' => $vendor->products_count ?? 0,
            'is_verified' => $vendor->isVendorVerified(),
            'is_online' => false,
            'whatsapp_number' => $vendor->whatsapp_number ?? null,
        ];

        $relatedProductsData = $relatedProducts->map(function ($related) {
            $image = $related->media->first();
            $vendorAvatar = $related->vendor !== null ? $related->vendor->media->where('type', 'avatar')->first() : null;

            $imageUrl = $image ? $this->mediaService->getMediaUrl($image) : null;

            return [
                'id' => $related->id,
                'name' => $related->name,
                'slug' => $related->slug,
                'price' => $related->price,
                'images' => $imageUrl ? [['url' => $imageUrl, 'is_primary' => true, 'type' => $image->type]] : [],
                'vendor' => $related->vendor ? [
                    'id' => $related->vendor->id,
                    'slug' => $related->vendor->slug,
                    'name' => $related->vendor->name,
                    'business_name' => $related->vendor->business_name ?? $related->vendor->name,
                    'business_logo' => $vendorAvatar ? $this->mediaService->getMediaUrl($vendorAvatar) : null,
                ] : null,
            ];
        });

        return Inertia::render('Products/Show', [
            'product' => $productData,
            'vendor' => $vendorData,
            'vendor_stats' => $vendorStats,
            'related_products' => $relatedProductsData,
        ]);
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('Products/Edit', [
            'product' => $product,
        ]);
    }

    public function update(ProductFormRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validated();

        $this->productService->updateProduct($product->id, $validated);

        return redirect()
            ->route('products.show', $product->slug)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->deleteProduct($product->id);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    public function uploadImages(ImageUploadRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $files = $request->file('images');
        $files = is_array($files) ? $files : ($files ? [$files] : []);

        $result = $this->mediaService->uploadMultipleImages(
            files: $files,
            modelType: Product::class,
            modelId: $product->id,
            type: 'product_image'
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload images.',
                'errors' => $result['errors'] ?? [],
            ], 422);
        }

        // Format media response
        $mediaUrls = $this->formatMediaResponse($result['media']);

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully.',
            'media' => $mediaUrls,
            'errors' => $result['errors'] ?? [],
        ]);
    }

    public function deleteImage(Product $product, int $mediaId): JsonResponse
    {
        $this->authorize('update', $product);

        // Verify media belongs to this product
        $media = $product->media()->find($mediaId);
        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found for this product.',
            ], 404);
        }

        $success = $this->mediaService->deleteMedia($mediaId);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Image deleted successfully.',
        ]);
    }

    /**
     * Get all images for a product
     */
    public function getImages(Product $product): JsonResponse
    {
        $mediaUrls = $this->formatMediaResponse($product->media, includeType: true);

        return response()->json([
            'success' => true,
            'media' => $mediaUrls,
        ]);
    }

    /**
     * Check whether the authenticated vendor has reached their plan's product limit.
     * Returns the error message string when at limit, or null when creation is allowed.
     */
    private function checkProductLimit(): ?string
    {
        $vendor = Auth::user();
        $subscription = $this->subscriptionService->getVendorSubscription($vendor->id);

        if ($subscription === null) {
            return null; // No subscription found — let the policy / other guards handle access
        }

        $activeCount = $vendor->products()->where('is_active', true)->count();

        if ($activeCount >= $subscription->plan->product_limit) {
            return "You have reached the product limit for your {$subscription->plan->name} plan. Upgrade to add more products.";
        }

        return null;
    }

    /**
     * Format media collection for JSON response
     *
     * @param  iterable<int, Media>  $media
     * @return array<int, array<string, mixed>>
     */
    private function formatMediaResponse(iterable $media, bool $includeType = false): array
    {
        return collect($media)->map(function (Media $mediaItem) use ($includeType) {
            $data = [
                'id' => $mediaItem->id,
                'url' => $this->mediaService->getMediaUrl($mediaItem),
            ];

            if ($includeType) {
                $data['type'] = $mediaItem->type;
            }

            return $data;
        })->all();
    }
}
