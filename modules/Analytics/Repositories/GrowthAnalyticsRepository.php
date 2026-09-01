<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Analytics\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Billing\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Identity\Enums\UserEnum;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;

class GrowthAnalyticsRepository extends BasePageRepository
{
    private const USERS = 'users';

    private const PRODUCTS = 'products';

    private const INTERACTIONS = 'whatsapp_interactions';

    private const SUBSCRIPTIONS = 'vendor_subscriptions';

    private const ONBOARDINGS = 'vendor_onboardings';

    public function newVendorsByDate(Carbon $since): Collection
    {
        return $this->dailyTotals(self::USERS, $since, $this->roleIs(UserEnum::VENDOR->value));
    }

    public function newCustomersByDate(Carbon $since): Collection
    {
        return $this->dailyTotals(self::USERS, $since, $this->roleIs(UserEnum::CUSTOMER->value));
    }

    public function newProductsByDate(Carbon $since): Collection
    {
        return $this->dailyTotals(self::PRODUCTS, $since, $this->liveProduct());
    }

    public function interactionsByDate(WhatsAppInteractionEventEnum $event, Carbon $since): Collection
    {
        return $this->dailyTotals(self::INTERACTIONS, $since, $this->eventIs($event));
    }

    public function newSubscriptionsByDate(Carbon $since): Collection
    {
        return $this->dailyTotals(self::SUBSCRIPTIONS, $since);
    }

    public function vendorsBefore(Carbon $since): int
    {
        return $this->countBefore(self::USERS, $since, $this->roleIs(UserEnum::VENDOR->value));
    }

    public function productsBefore(Carbon $since): int
    {
        return $this->countBefore(self::PRODUCTS, $since, $this->liveProduct());
    }

    public function activeVendorDays(Carbon $since): Collection
    {
        return DB::table(self::INTERACTIONS)
            ->where('event_type', WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value)
            ->where('created_at', '>=', $since)
            ->whereNotNull('vendor_id')
            ->selectRaw('vendor_id, DATE(created_at) as date')
            ->distinct()
            ->get();
    }

    public function countVendors(): int
    {
        return DB::table(self::USERS)->where('role', UserEnum::VENDOR->value)->count();
    }

    public function countApprovedOnboardings(): int
    {
        return DB::table(self::ONBOARDINGS)
            ->where('status', VendorOnboardingStatusEnum::APPROVED->value)
            ->count();
    }

    public function countVendorsWithMinimumProducts(int $minimum): int
    {
        $vendors = DB::table(self::PRODUCTS)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('vendor_id')
            ->groupBy('vendor_id')
            ->havingRaw('COUNT(*) >= ?', [$minimum]);

        return DB::query()->fromSub($vendors, 'sub')->count();
    }

    public function countContactedVendors(?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = DB::table(self::INTERACTIONS)
            ->where('event_type', WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value)
            ->whereNotNull('vendor_id');

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<', $to);
        }

        return $query->distinct()->count('vendor_id');
    }

    public function activeSubscriptionPeriods(Carbon $asOf): Collection
    {
        return DB::table(self::SUBSCRIPTIONS)
            ->where('status', VendorSubscriptionStatusEnum::ACTIVE->value)
            ->where('expires_at', '>', $asOf)
            ->where('amount_paid', '>', 0)
            ->select('amount_paid', 'started_at', 'expires_at')
            ->get();
    }

    public function lifetimeCollected(): float
    {
        return round((float) DB::table(self::SUBSCRIPTIONS)->sum('amount_paid'), 2);
    }

    public function topUnmetSearches(Carbon $since, int $limit): Collection
    {
        return DB::table(self::INTERACTIONS)
            ->where('event_type', WhatsAppInteractionEventEnum::NO_RESULTS->value)
            ->where('created_at', '>=', $since)
            ->whereNotNull('search_query')
            ->where('search_query', '!=', '')
            ->selectRaw('search_query, COUNT(*) as count')
            ->groupBy('search_query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    private function dailyTotals(string $table, Carbon $since, ?callable $constrain = null): Collection
    {
        $query = DB::table($table)->where('created_at', '>=', $since);

        if ($constrain !== null) {
            $constrain($query);
        }

        return $query
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    private function countBefore(string $table, Carbon $since, ?callable $constrain = null): int
    {
        $query = DB::table($table)->where('created_at', '<', $since);

        if ($constrain !== null) {
            $constrain($query);
        }

        return $query->count();
    }

    private function roleIs(string $role): callable
    {
        return static fn (Builder $query) => $query->where('role', $role);
    }

    private function eventIs(WhatsAppInteractionEventEnum $event): callable
    {
        return static fn (Builder $query) => $query->where('event_type', $event->value);
    }

    private function liveProduct(): callable
    {
        return static fn (Builder $query) => $query->where('is_active', true)->whereNull('deleted_at');
    }
}
