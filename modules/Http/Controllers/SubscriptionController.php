<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Services\Payments\AmountMismatchException;
use ModulesShoppingComplex\Services\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Services\Payments\Stellar\AnchorUnavailableException;
use ModulesShoppingComplex\Services\Payments\Stellar\StellarDepositService;
use ModulesShoppingComplex\Services\Payments\Stellar\StellarTestnetFunder;
use ModulesShoppingComplex\Services\Payments\Stellar\StellarWalletService;
use ModulesShoppingComplex\Services\SubscriptionAuthorizationService;
use ModulesShoppingComplex\Services\SubscriptionService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SubscriptionController extends Controller
{
    /** Cycles of NGNC to pre-load into a vendor's custodial wallet on testnet enrolment. */
    private const STARTER_CYCLES = 3;

    private const DEFAULT_METHOD = PaymentMethodEnum::PAYSTACK;

    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly StellarDepositService $stellarDeposits,
        private readonly SubscriptionAuthorizationService $authorizations,
        private readonly StellarWalletService $wallets,
        private readonly StellarTestnetFunder $funder,
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

        $authorization = $this->authorizations->chargeableFor($vendor->id);

        return Inertia::render('Vendor/Subscription/Index', [
            'plans' => $this->subscriptionService->getPlans(),
            'currentSubscription' => $this->subscriptionService->getVendorSubscription($vendor->id),
            'productsCount' => $vendor->products()->where('is_active', true)->count(),
            'autoRenew' => [
                'enabled' => $authorization !== null,
                'monthlyCap' => $authorization !== null ? (float) $authorization->monthly_cap : null,
                'validUntil' => $authorization?->valid_until?->toIso8601String(),
            ],
        ]);
    }

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

        return back()->with('stellarCheckout', [
            'url' => $session->url,
            'reference' => $session->reference,
        ]);
    }

    /**
     * Poll a Stellar deposit's status (called repeatedly by the deposit modal).
     * On completion it activates the subscription.
     */
    public function stellarStatus(Request $request): JsonResponse
    {
        if (Auth::user()->role !== 'vendor') {
            return response()->json(['status' => 'error', 'message' => 'Only vendors can manage subscriptions.'], 403);
        }

        $reference = (string) $request->query('reference', '');
        if ($reference === '') {
            return response()->json(['status' => 'error', 'message' => 'Missing deposit reference.'], 422);
        }

        $vendor = Auth::user();

        try {
            $transaction = $this->stellarDeposits->syncStatus($vendor, $reference);
        } catch (AnchorUnavailableException $e) {
            return response()->json(['status' => 'pending'], 200);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }

        if ($transaction->isCompleted()) {
            try {
                $this->subscriptionService->handleCallback(PaymentMethodEnum::STELLAR, $reference, $vendor);
            } catch (AmountMismatchException $e) {
                $this->stellarDeposits->markAmountMismatch($vendor, $reference);

                return response()->json([
                    'status' => 'error',
                    'message' => sprintf(
                        'You deposited %s but this plan costs %s, so it was not activated. Please contact support to arrange a refund or credit.',
                        $this->formatNaira($e->actual),
                        $this->formatNaira($e->expected),
                    ),
                ], 422);
            } catch (\RuntimeException $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
            }

            return response()->json(['status' => 'completed']);
        }

        if ($transaction->isFailed()) {
            return response()->json(['status' => 'failed']);
        }

        return response()->json(['status' => 'pending']);
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

    public function enableAutoRenew(): RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $vendor = Auth::user();
        $subscription = $this->subscriptionService->getVendorSubscription($vendor->id);

        if ($subscription === null) {
            return back()->with('error', 'Subscribe to a paid plan before enabling auto-renew.');
        }

        if ($subscription->plan->isFree()) {
            return back()->with('error', 'Auto-renew is only available on paid plans.');
        }

        try {
            $this->authorizations->authorize($vendor, $subscription->plan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Testnet only: seed the custodial wallet so the scheduled charge has NGNC to pull.
        // A funding hiccup must not undo the consent we just recorded, so surface it softly.
        if (config('services.stellar.network') !== 'public') {
            try {
                $wallet = $this->wallets->getOrCreateForVendor($vendor);
                $price = (float) $subscription->plan->price;

                if ($this->funder->ngncBalance($wallet) < $price) {
                    $this->funder->fund($wallet, $price * self::STARTER_CYCLES);
                }
            } catch (\Throwable $e) {
                Log::warning('Auto-renew wallet pre-funding failed', [
                    'vendor_id' => $vendor->id,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('success', 'Auto-renew is on. We could not pre-fund your test wallet yet — the first renewal may need a manual top-up.');
            }
        }

        return back()->with('success', 'Auto-renew is on. Your plan will renew automatically each month.');
    }

    public function disableAutoRenew(): RedirectResponse
    {
        if ($redirect = $this->denyNonVendor()) {
            return $redirect;
        }

        $this->authorizations->revoke(Auth::user());

        return back()->with('success', 'Auto-renew has been turned off.');
    }

    private function denyNonVendor(): ?RedirectResponse
    {
        if (Auth::user()->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can manage subscriptions.');
        }

        return null;
    }

    /** Format an amount as Naira for vendor-facing messages, e.g. 15000.0 -> "₦15,000.00". */
    private function formatNaira(float $amount): string
    {
        return '₦'.number_format($amount, 2);
    }
}
