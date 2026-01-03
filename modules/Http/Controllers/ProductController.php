<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\ProductFormRequest;
use ModulesShoppingComplex\Models\Category;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
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
}
