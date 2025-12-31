<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\ProductFormRequest;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of the products (publicly accessible).
     */
    public function index(): Response
    {
        $products = $this->productService->index(perPage: 20);

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('Products/Create');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductFormRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();

        $validated['vendor_id'] = Auth::id();

        $product = $this->productService->createProduct($validated);

        return redirect()->route('products.show', $product->id)->with('success', 'Product created successfully.');
    }

    /**
     * Display the specified product (publicly accessible).
     */
    public function show(Product $product): Response
    {
        $product = $this->productService->getProduct($product->id);

        return Inertia::render('Products/Show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        return Inertia::render('Products/Edit', [
            'product' => $product,
        ]);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductFormRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validated();

        $this->productService->updateProduct($product->id, $validated);

        return redirect()
            ->route('products.show', $product->id)
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->productService->deleteProduct($product->id);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
