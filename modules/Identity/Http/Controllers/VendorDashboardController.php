<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Analytics\Services\AnalyticsService;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Http\Requests\UpdateVendorProfileRequest;
use ModulesShoppingComplex\Identity\Models\Address;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Media\Services\MediaService;

class VendorDashboardController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly AnalyticsService $analyticsService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

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
            $product->images = $product->media->map(fn ($media) => [
                'id' => $media->id,
                'url' => $this->mediaService->getMediaUrl($media),
                'type' => $media->type,
                'is_primary' => true,
            ])->values()->all();

            return $product;
        });

        $subscription = $this->subscriptionService->getVendorSubscription($user->id);

        return Inertia::render('Vendor/Products', [
            'products' => $products,
            'vendor_slug' => $user->slug,
            'product_limit' => $subscription?->plan->product_limit ?? null,
            'active_products_count' => $user->products()->where('is_active', true)->count(),
        ]);
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
}
