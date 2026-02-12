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

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MediaService $mediaService,
        private readonly ReviewService $reviewService,
        private readonly AnalyticsService $analyticsService
    ) {}

    public function index(): Response
    {
        $products = $this->productService->index(perPage: 20);
        $categories = Cache::remember('product_index_categories', 3600, fn () => Category::withCount('products')->get()
        );

        $products->through(function ($product) {
            $product->images = $product->media->map(fn ($media) => [
                'id' => $media->id,
                'url' => $this->mediaService->getMediaUrl($media),
                'is_primary' => true,
            ])->values()->all();

            return $product;
        });

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Products/Create');
    }

    public function store(ProductFormRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();

        $validated['vendor_id'] = Auth::id();

        $product = $this->productService->createProduct($validated);

        return redirect()->route('products.show', $product->id)->with('success', 'Product created successfully.');
    }

    public function show(Product $product): Response
    {
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
        $vendorReviews = $this->reviewService->getVendorReviews($vendor->id, 5);

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
            'name' => $vendor->name,
            'email' => $vendor->email,
            'email_verified_at' => $vendor->email_verified_at,
            'created_at' => $vendor->created_at,
            'updated_at' => $vendor->updated_at,
            'role' => 'vendor',
            'business_name' => $vendor->business_name ?? $vendor->name,
            'business_description' => $vendor->bio,
            'business_logo' => $avatarMedia ? $this->mediaService->getMediaUrl($avatarMedia) : null,
            'rating' => $vendorStats['average'],
            'total_sales' => 0,
            'products_count' => $vendor->products_count ?? 0,
            'is_verified' => $vendor->email_verified_at !== null,
            'is_online' => false,
        ];

        // Transform reviews for frontend
        $reviewsData = [
            'reviews' => $vendorReviews->items(),
            'meta' => [
                'current_page' => $vendorReviews->currentPage(),
                'last_page' => $vendorReviews->lastPage(),
                'per_page' => $vendorReviews->perPage(),
                'total' => $vendorReviews->total(),
            ],
        ];

        return Inertia::render('Products/Show', [
            'product' => $productData,
            'vendor' => $vendorData,
            'vendor_stats' => $vendorStats,
            'vendor_reviews' => $reviewsData,
            'related_products' => $relatedProducts,
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
            ->route('products.show', $product->id)
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
