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
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Http\Requests\UpdateVendorProfileRequest;
use ModulesShoppingComplex\Identity\Models\Address;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use ModulesShoppingComplex\Media\Services\MediaService;

class VendorDashboardController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
        private readonly AnalyticsService $analyticsService,
        private readonly SubscriptionService $subscriptionService,
        private readonly ReferralService $referralService,
        private readonly CoinWalletService $wallet,
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

        $referralCode = $this->referralService->codeFor($user);
        $referralTally = $this->referralService->referralTallyFor($user);

        $balance = $this->wallet->balance($user);
        $lowBalanceThreshold = (int) config('billing.leads.low_balance_leads', 3)
            * (int) config('billing.leads.default_cost', 5);
        $missedLeadsCount = BillableLead::where('vendor_id', $user->id)
            ->where('state', BillableLeadStateEnum::UNBILLED)
            ->count();
        $chargedToday = (int) BillableLead::where('vendor_id', $user->id)
            ->where('state', BillableLeadStateEnum::CHARGED)
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('coins_charged');

        return Inertia::render('Vendor/Dashboard', [
            'vendor' => [
                'name' => $user->name,
                'business_name' => $user->business_name ?? $user->name,
                'slug' => $user->slug,
            ],
            'referral' => [
                'code' => $referralCode,
                'link' => $this->referralService->shareUrl($referralCode),
                'count' => $referralTally['qualified'],
                'referred_count' => $referralTally['referred'],
                'min_products' => User::minReferralProducts(),
                'recent' => $this->referralService->recentReferralsFor($user),
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
            'coins' => [
                'balance' => $balance,
                'low_balance' => $balance < $lowBalanceThreshold,
                'low_balance_threshold' => $lowBalanceThreshold,
                'missed_leads' => $missedLeadsCount,
                'daily_coin_cap' => $user->daily_coin_cap,
                'charged_today' => $chargedToday,
                'top_up_link' => route('vendor.coins.packs'),
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
            $attributes = [
                'business_name' => $request->input('business_name'),
                'bio' => $request->input('bio'),
                'whatsapp_number' => $request->input('whatsapp_number'),
            ];

            if ($request->has('daily_coin_cap')) {
                $attributes['daily_coin_cap'] = $request->input('daily_coin_cap');
            }

            $user->update($attributes);

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
