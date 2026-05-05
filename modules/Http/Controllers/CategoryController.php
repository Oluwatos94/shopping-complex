<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\Category;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Services\MediaService;

class CategoryController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    public function index(): Response
    {
        $categories = Category::orderBy('name')->get(['id', 'name', 'slug', 'description']);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function vendors(int $id): Response
    {
        $category = Category::findOrFail($id);

        $vendors = User::where('role', 'vendor')
            ->whereHas('vendorOnboarding', fn ($q) => $q->where('status', VendorOnboardingStatusEnum::APPROVED))
            ->whereHas('products', fn ($q) => $q->where('category_id', $id))
            ->with(['media', 'products' => fn ($q) => $q->where('category_id', $id)->limit(3)])
            ->paginate(12);

        $transformedVendors = $vendors->through(function ($vendor) {
            $avatarMedia = $vendor->media->where('type', 'avatar')->first();

            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'slug' => $vendor->slug,
                'profileImage' => $avatarMedia ? $this->mediaService->getMediaUrl($avatarMedia) : null,
                'products' => $vendor->products->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                ]),
            ];
        });

        return Inertia::render('Categories/Vendors', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'vendors' => $transformedVendors,
        ]);
    }

    public function products(int $id): Response
    {
        $category = Category::findOrFail($id);

        $products = Product::where('category_id', $id)
            ->where('is_active', true)
            ->with(['media', 'vendor'])
            ->paginate(16);

        $transformedProducts = $products->through(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'image' => ($m = $product->media->first()) ? $this->mediaService->getMediaUrl($m) : null,
                'media_type' => $product->media->first()?->type,
                'vendor_name' => $product->vendor ? ($product->vendor->business_name ?? $product->vendor->name) : null,
                'vendor_slug' => $product->vendor?->slug,
            ];
        });

        return Inertia::render('Categories/Products', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'products' => $transformedProducts,
        ]);
    }
}
