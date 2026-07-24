<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Analytics\Services\AnalyticsService;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    /**
     * Render the analytics dashboard page.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $user = Auth::user();

        if (! $user || $user->role !== 'vendor') {
            abort(403, 'Only vendors can access analytics.');
        }

        [$startDate, $endDate] = $this->analyticsService->resolveDateRange(
            $request->get('period'),
            $request->get('start_date'),
            $request->get('end_date')
        );

        $limit = min(max((int) $request->get('limit', 10), 1), 50);

        $overview = $this->analyticsService->getOverview($user->id, $startDate, $endDate);
        $chatContacts = $this->analyticsService->getChatContactMetrics($user->id, $startDate, $endDate);
        $profileViews = $this->analyticsService->getProfileViewMetrics($user->id, $startDate, $endDate);
        $topProducts = $this->analyticsService->getTopProducts($user->id, $startDate, $endDate, $limit);
        $whatsAppMetrics = $this->analyticsService->getWhatsAppMetrics($user->id, $startDate, $endDate);

        $subscription = $this->subscriptionService->getVendorSubscription($user->id);
        $isFree = $subscription?->plan->isFree() ?? false;
        $daysRemaining = null;
        if ($subscription !== null && ! $isFree) {
            $daysRemaining = max(0, (int) now()->diffInDays($subscription->expires_at, false));
        }

        $data = [
            'overview' => $overview,
            'chatContacts' => $chatContacts,
            'profileViews' => $profileViews,
            'topProducts' => $topProducts,
            'whatsAppMetrics' => $whatsAppMetrics,
            'subscription' => [
                'plan_name' => $subscription?->plan->name,
                'plan_slug' => $subscription?->plan->slug,
                'expires_at' => ($subscription !== null && ! $isFree) ? $subscription->expires_at->toISOString() : null,
                'days_remaining' => $daysRemaining,
                'product_limit' => $subscription?->plan->product_limit,
            ],
        ];

        // Support AJAX requests for dynamic date filtering
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Vendor/Analytics', $data);
    }
}
