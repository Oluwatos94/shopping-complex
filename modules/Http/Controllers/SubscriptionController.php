<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Services\SubscriptionService;

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
        $vendor = Auth::user();

        if ($vendor->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can manage subscriptions.');
        }

        return Inertia::render('Vendor/Subscription/Index', [
            'plans' => $this->subscriptionService->getPlans(),
            'currentSubscription' => $this->subscriptionService->getVendorSubscription($vendor->id),
        ]);
    }

    /**
     * Initiate a Paystack payment for the selected plan and redirect the vendor to Paystack.
     */
    public function checkout(SubscriptionPlan $plan): RedirectResponse
    {
        $vendor = Auth::user();

        if ($vendor->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can manage subscriptions.');
        }

        if ($plan->isFree()) {
            return back()->with('error', 'The free plan is automatically assigned upon vendor approval.');
        }

        try {
            $authorizationUrl = $this->subscriptionService->initiatePayment($vendor, $plan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->away($authorizationUrl);
    }

    /**
     * Handle the Paystack redirect callback after payment.
     * Verifies the transaction and activates the subscription on success.
     */
    public function callback(Request $request): RedirectResponse
    {
        $vendor = Auth::user();

        if ($vendor->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can manage subscriptions.');
        }

        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('vendor.subscription.index')
                ->with('error', 'Invalid payment reference.');
        }

        try {
            $this->subscriptionService->handlePaystackCallback($reference, $vendor->id);
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
        $vendor = Auth::user();

        if ($vendor->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can manage subscriptions.');
        }

        try {
            $this->subscriptionService->cancelSubscription($vendor);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Your subscription has been cancelled.');
    }
}
