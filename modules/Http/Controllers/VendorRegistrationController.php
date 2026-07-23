<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Http\Requests\VendorRegisterRequest;
use ModulesShoppingComplex\Services\VendorService;

class VendorRegistrationController extends Controller
{
    public function __construct(
        private readonly VendorService $vendorService,
    ) {}

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
}
