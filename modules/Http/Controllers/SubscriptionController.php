<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Services\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Services\SubscriptionService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Show the vendor's subscription page with all available plans.
     */
    public function index(): Response|RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $vendor = Auth::user();

        return Inertia::render('Vendor/Subscription/Index', [
            'plans' => $this->subscriptionService->getPlans(),
            'currentSubscription' => $this->subscriptionService->getVendorSubscription($vendor->id),
            'productsCount' => $vendor->products()->where('is_active', true)->count(),
        ]);
    }

    private const DEFAULT_METHOD = PaymentMethodEnum::PAYSTACK;

    /**
     * Start a payment for the selected plan on the chosen rail and hand the vendor off to pay.
     */
    public function checkout(Request $request, SubscriptionPlan $plan): RedirectResponse|SymfonyResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        if ($plan->isFree()) {
            return back()->with('error', 'The free plan is automatically assigned upon vendor approval.');
        }

        $vendor = Auth::user();
        $method = PaymentMethodEnum::tryFrom((string) $request->input('method')) ?? self::DEFAULT_METHOD;

        try {
            $session = $this->subscriptionService->initiatePayment($vendor, $plan, $method);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($session->type === CheckoutTypeEnum::REDIRECT) {
            return Inertia::location($session->url);
        }

        return back()->with('stellarCheckoutUrl', $session->url);
    }

    /**
     * Handle the Paystack redirect callback after payment.
     * Verifies the transaction and activates the subscription on success.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $reference = (string) $request->query('reference', '');

        if ($reference === '') {
            return redirect()->route('vendor.subscription.index')
                ->with('error', 'Invalid payment reference.');
        }

        try {
            $this->subscriptionService->handleCallback(PaymentMethodEnum::PAYSTACK, $reference, Auth::user());
        } catch (\RuntimeException $e) {
            return redirect()->route('vendor.subscription.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('vendor.subscription.index')
            ->with('success', 'Your subscription has been activated successfully!');
    }

    /**
     * Cancel the vendor's active subscription.
     */
    public function cancel(): RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        try {
            $this->subscriptionService->cancelSubscription(Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Your subscription has been cancelled.');
    }

    /**
     * Return a redirect if the authenticated user is not a vendor, otherwise null.
     * Centralises the vendor-only guard to avoid repeating it in every action.
     */
    private function denyNonVendor(): ?RedirectResponse
    {
        if (Auth::user()->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can manage subscriptions.');
        }

        return null;
    }
}
