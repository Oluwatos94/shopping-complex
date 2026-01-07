<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\ImageUploadRequest;
use ModulesShoppingComplex\Http\Requests\ProductFormRequest;
use ModulesShoppingComplex\Models\Category;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Services\MediaService;
use ModulesShoppingComplex\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MediaService $mediaService
    ) {}

    public function index(): Response
    {
        $products = $this->productService->index(perPage: 20);
        $categories = Category::withCount('products')->get();

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

        return Inertia::render('Products/Show', [
            'product' => $product,
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
     * @param  iterable<Media>  $media
     * @return array<array<string, mixed>>
     */
    private function formatMediaResponse(iterable $media, bool $includeType = false): array
    {
        return collect($media)->map(function ($mediaItem) use ($includeType) {
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
