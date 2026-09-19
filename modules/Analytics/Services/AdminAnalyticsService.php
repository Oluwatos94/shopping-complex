<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Analytics\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Billing\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Billing\Models\AnchorTransaction;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Models\VendorOnboarding;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use ModulesShoppingComplex\Shared\Pagination\PageSize;
use ModulesShoppingComplex\Shared\Support\LikeTerm;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;

final readonly class AdminAnalyticsService
{
    private const MAX_PER_PAGE = 100;

    private const PARTICIPANT_REFERRALS_LIMIT = 50;

    public function __construct(
        private ReferralService $referralService,
    ) {}

    /**
     * Get platform-wide statistics using aggregated queries.
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
            'products' => [
                'total' => Product::count(),
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
            $search = LikeTerm::escape((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = PageSize::resolve($filters['per_page'] ?? null, max: self::MAX_PER_PAGE);

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
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function getAllVendors(int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $query = User::query()
            ->where('role', 'vendor')
            ->with('vendorOnboarding')
            ->withCount('products');

        if ($search !== null && trim($search) !== '') {
            $term = LikeTerm::escape(trim($search));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $paginator = $query->latest()->paginate($perPage);

        $items = $paginator->getCollection()
            ->map(fn (User $user): array => $this->toApplicationShape($user))
            ->all();

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    public function getCampaignParticipants(array $filters): array
    {
        $perPage = PageSize::resolve($filters['per_page'] ?? null, max: self::MAX_PER_PAGE);
        $search = trim((string) ($filters['search'] ?? ''));

        return [
            'total_participants' => $this->referralService->totalParticipants(),
            'participants' => $this->referralService->participants($search === '' ? null : $search, $perPage),
        ];
    }

    /**
     * One participant's drill-in: the vendor application admins already review,
     * plus their campaign standing and who they brought in.
     *
     * @return array<string, mixed>
     */
    public function getCampaignParticipant(User $user): array
    {
        $user->loadMissing('vendorOnboarding')->loadCount('products');

        $standing = $this->referralService->standingFor($user);

        if ($standing === null) {
            $tally = $this->referralService->referralTallyFor($user);
            $standing = ['rank' => null, 'referral_count' => $tally['qualified'], 'referred_count' => $tally['referred']];
        }

        return [
            ...$this->toApplicationShape($user),
            'referral_count' => $standing['referral_count'],
            'referred_count' => $standing['referred_count'],
            'min_products' => User::minReferralProducts(),
            'rank' => $standing['rank'],
            'referrals' => $this->referralService->recentReferralsFor($user, self::PARTICIPANT_REFERRALS_LIMIT),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toApplicationShape(User $user): array
    {
        $productsCount = (int) ($user->products_count ?? 0);

        $base = [
            'user_id' => $user->id,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'business_name' => $user->business_name,
            ],
            'products_count' => $productsCount,
            'created_at' => $user->created_at?->toISOString(),
        ];

        $onboarding = $user->vendorOnboarding;

        if ($onboarding === null) {
            return [
                ...$base,
                'id' => $user->id,
                'legal_entity_name' => null,
                'business_category' => null,
                'tax_identification_number' => null,
                'physical_address' => null,
                'bank_name' => null,
                'bank_branch' => null,
                'account_number' => null,
                'certificate_of_incorporation' => null,
                'government_issued_id' => null,
                'proof_of_address' => null,
                'status' => 'registered',
                'current_step' => 0,
                'agreed_to_terms' => false,
                'rejection_reason' => null,
                'reviewed_at' => null,
            ];
        }

        return [
            ...$base,
            'id' => $onboarding->id,
            'legal_entity_name' => $onboarding->legal_entity_name,
            'business_category' => $onboarding->business_category,
            'tax_identification_number' => $onboarding->tax_identification_number,
            'physical_address' => $onboarding->physical_address,
            'bank_name' => $onboarding->bank_name,
            'bank_branch' => $onboarding->bank_branch,
            'account_number' => $onboarding->account_number,
            'certificate_of_incorporation' => $onboarding->certificate_of_incorporation,
            'government_issued_id' => $onboarding->government_issued_id,
            'proof_of_address' => $onboarding->proof_of_address,
            'status' => $onboarding->status->value,
            'current_step' => $onboarding->current_step,
            'agreed_to_terms' => $onboarding->agreed_to_terms,
            'rejection_reason' => $onboarding->rejection_reason,
            'reviewed_at' => $onboarding->reviewed_at?->toISOString(),
        ];
    }

    /**
     *      * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function getPaidCoinPurchases(array $filters): LengthAwarePaginator
    {
        $stellarPurchaseIds = AnchorTransaction::query()
            ->where('kind', AnchorTransactionKindEnum::COIN_DEPOSIT->value)
            ->whereNotNull('coin_purchase_id')
            ->pluck('coin_purchase_id');

        $query = CoinPurchase::query()
            ->with('vendor:id,name,business_name,email')
            ->where('status', CoinPurchaseStatusEnum::COMPLETED->value);

        $method = $filters['method'] ?? null;

        if ($method === 'stellar') {
            $query->whereIn('id', $stellarPurchaseIds);
        } elseif ($method === 'paystack') {
            $query->whereNotIn('id', $stellarPurchaseIds);
        }

        $perPage = PageSize::resolve($filters['per_page'] ?? null, max: self::MAX_PER_PAGE);
        $purchases = $query->latest('paid_at')->paginate($perPage);

        $hashes = AnchorTransaction::query()
            ->whereIn('coin_purchase_id', $purchases->getCollection()->pluck('id'))
            ->where('kind', AnchorTransactionKindEnum::COIN_DEPOSIT->value)
            ->whereNotNull('stellar_tx_hash')
            ->pluck('stellar_tx_hash', 'coin_purchase_id');

        /** @var list<array<string, mixed>> $items */
        $items = $purchases->getCollection()->map(function (CoinPurchase $purchase) use ($hashes): array {
            $vendor = $purchase->vendor;

            return [
                'id' => $purchase->id,
                'vendor' => [
                    'name' => $vendor === null ? 'Unknown' : ($vendor->business_name ?? $vendor->name),
                    'email' => $vendor?->email,
                ],
                'pack' => $purchase->pack,
                'coins' => $purchase->totalCoins(),
                'amount' => $purchase->price,
                'method' => $hashes->has($purchase->id) ? 'stellar' : 'paystack',
                'tx_hash' => $hashes->get($purchase->id),
                'paid_at' => $purchase->paid_at?->toIso8601String(),
            ];
        })->all();

        return new LengthAwarePaginator(
            $items,
            $purchases->total(),
            $purchases->perPage(),
            $purchases->currentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }

    /**
     * Get platform-wide WhatsApp bot statistics for the admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getPlatformBotStats(): array
    {
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

        $completed = CoinPurchaseStatusEnum::COMPLETED->value;

        $monthlyRevenue = DB::table('coin_purchases')
            ->where('status', $completed)
            ->where('paid_at', '>=', $startOfMonth)
            ->sum('price');

        $paidVendors = DB::table('coin_purchases')
            ->where('status', $completed)
            ->distinct()
            ->count('vendor_id');

        return [
            'total_searches' => (int) ($eventCounts[WhatsAppInteractionEventEnum::SEARCH->value] ?? 0),
            'total_contacts_made' => (int) ($eventCounts[WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value] ?? 0),
            'total_no_results' => (int) ($eventCounts[WhatsAppInteractionEventEnum::NO_RESULTS->value] ?? 0),
            'searches_this_month' => (int) ($monthlyEventCounts[WhatsAppInteractionEventEnum::SEARCH->value] ?? 0),
            'contacts_this_month' => (int) ($monthlyEventCounts[WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value] ?? 0),
            'paid_vendors' => (int) $paidVendors,
            'monthly_revenue' => round((float) $monthlyRevenue, 2),
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
