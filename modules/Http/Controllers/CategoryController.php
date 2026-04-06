<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\Category;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\User;

class CategoryController extends Controller
{
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
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'slug' => $vendor->slug,
                'profileImage' => $vendor->media->first()?->file_path,
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
}
