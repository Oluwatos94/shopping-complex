<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\AnchorTransaction;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Models\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;
use ModulesShoppingComplex\Models\VendorSubscription;

final readonly class AdminAnalyticsService
{
    /**
     * Get platform-wide statistics using 3 aggregated queries.
     *
     * @return array<string, mixed>
     */
    public function getPlatformStats(): array
    {
        $userCounts = User::selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $onboardingCounts = VendorOnboarding::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'users' => [
                'total' => (int) $userCounts->sum(),
                'admins' => (int) ($userCounts['admin'] ?? 0),
                'vendors' => (int) ($userCounts['vendor'] ?? 0),
                'customers' => (int) ($userCounts['customer'] ?? 0),
            ],
            'vendors' => [
                'approved' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::APPROVED->value] ?? 0),
                'pending_review' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::PENDING_REVIEW->value] ?? 0),
                'rejected' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::REJECTED->value] ?? 0),
                'draft' => (int) ($onboardingCounts[VendorOnboardingStatusEnum::DRAFT->value] ?? 0),
            ],
        ];
    }

    /**
     * Get paginated user list with optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<User>
     */
    public function getUserList(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with('vendorOnboarding');

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get paginated pending vendor applications.
     *
     * @return LengthAwarePaginator<VendorOnboarding>
     */
    public function getPendingVendors(int $perPage = 20, string $status = 'pending_review'): LengthAwarePaginator
    {
        $allowed = VendorOnboardingStatusEnum::values();
        $status = in_array($status, $allowed, true) ? $status : VendorOnboardingStatusEnum::PENDING_REVIEW->value;

        return VendorOnboarding::with('user')
            ->where('status', $status)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get paginated paid vendor subscriptions. Stellar-rail rows carry their on-chain
     * transaction history (deposit + each mpp_charge) with settled tx hashes.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<VendorSubscription>
     */
    public function getPaidSubscriptions(array $filters): LengthAwarePaginator
    {
        $query = VendorSubscription::query()
            ->with(['vendor:id,name,business_name,email', 'plan:id,name,price'])
            ->whereNotNull('amount_paid');

        if (! empty($filters['method']) && PaymentMethodEnum::tryFrom($filters['method']) !== null) {
            $query->where('payment_method', $filters['method']);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? 20), 1), 100);
        $subscriptions = $query->latest()->paginate($perPage);

        $stellarVendorIds = $subscriptions->getCollection()
            ->where('payment_method', PaymentMethodEnum::STELLAR)
            ->pluck('vendor_id')
            ->unique()
            ->values();

        $hashesByVendor = $stellarVendorIds->isEmpty()
            ? collect()
            : AnchorTransaction::query()
                ->whereIn('vendor_id', $stellarVendorIds)
                ->whereNotNull('stellar_tx_hash')
                ->orderBy('completed_at')
                ->get(['vendor_id', 'kind', 'amount', 'billing_period', 'stellar_tx_hash', 'completed_at'])
                ->groupBy('vendor_id');

        $subscriptions->getCollection()->transform(function (VendorSubscription $sub) use ($hashesByVendor) {
            $history = $hashesByVendor->get($sub->vendor_id, collect())
                ->map(fn (AnchorTransaction $tx): array => [
                    'kind' => $tx->kind->value,
                    'amount' => (float) $tx->amount,
                    'billing_period' => $tx->billing_period,
                    'hash' => $tx->stellar_tx_hash,
                    'completed_at' => $tx->completed_at?->toIso8601String(),
                ])
                ->values();

            $sub->setAttribute('stellar_transactions', $history);

            return $sub;
        });

        return $subscriptions;
    }

    /**
     * Get platform-wide WhatsApp bot statistics for the admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getPlatformBotStats(): array
    {
        $activeStatus = VendorSubscriptionStatusEnum::ACTIVE->value;
        $startOfMonth = now()->startOfMonth();

        $eventCounts = DB::table('whatsapp_interactions')
            ->selectRaw('event_type, COUNT(*) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        $monthlyEventCounts = DB::table('whatsapp_interactions')
            ->where('created_at', '>=', $startOfMonth)
            ->selectRaw('event_type, COUNT(*) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        $subscriptionStats = DB::table('vendor_subscriptions')
            ->where('status', $activeStatus)
            ->where('expires_at', '>', now())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_paid), 0) as revenue')
            ->first();

        return [
            'total_searches' => (int) ($eventCounts[WhatsAppInteractionEventEnum::SEARCH->value] ?? 0),
            'total_contacts_made' => (int) ($eventCounts[WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value] ?? 0),
            'total_no_results' => (int) ($eventCounts[WhatsAppInteractionEventEnum::NO_RESULTS->value] ?? 0),
            'searches_this_month' => (int) ($monthlyEventCounts[WhatsAppInteractionEventEnum::SEARCH->value] ?? 0),
            'contacts_this_month' => (int) ($monthlyEventCounts[WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value] ?? 0),
            'active_subscribed_vendors' => (int) ($subscriptionStats->count ?? 0),
            'monthly_revenue' => round((float) ($subscriptionStats->revenue ?? 0), 2),
        ];
    }

    /**
     * Get paginated recent WhatsApp interactions for the bot monitor page.
     *
     * @return LengthAwarePaginator<\stdClass>
     */
    public function getRecentInteractions(int $perPage = 50): LengthAwarePaginator
    {
        return DB::table('whatsapp_interactions')
            ->leftJoin('users', 'whatsapp_interactions.vendor_id', '=', 'users.id')
            ->select([
                'whatsapp_interactions.id',
                'whatsapp_interactions.phone_number',
                'whatsapp_interactions.event_type',
                'whatsapp_interactions.search_query',
                'whatsapp_interactions.vendor_id',
                'users.business_name as vendor_name',
                'whatsapp_interactions.buyer_latitude',
                'whatsapp_interactions.buyer_longitude',
                'whatsapp_interactions.created_at',
            ])
            ->orderByDesc('whatsapp_interactions.created_at')
            ->paginate($perPage);
    }
}
