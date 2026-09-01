<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Analytics\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ModulesShoppingComplex\Analytics\Repositories\GrowthAnalyticsRepository;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;

final readonly class GrowthAnalyticsService
{
    private const CACHE_TTL = 300;

    private const WEEK_BUCKETS = 13;

    private const MONTH_BUCKETS = 6;

    private const LISTED_THRESHOLD = 1;

    private const UNMET_DEMAND_LIMIT = 10;

    private const ACTIVE_WINDOW_DAYS = 30;

    private const DAYS_PER_MONTH = 30.44;

    private const HEADLINE_METRICS = [
        'new_vendors',
        'new_customers',
        'new_products',
        'contacts',
        'searches',
        'active_vendors',
    ];

    public function __construct(
        private GrowthAnalyticsRepository $repository,
    ) {}

    public function getGrowthMetrics(string $granularity = 'week'): array
    {
        $granularity = in_array($granularity, ['week', 'month'], true) ? $granularity : 'week';

        return Cache::remember(
            "admin:growth:{$granularity}",
            self::CACHE_TTL,
            fn (): array => $this->build($granularity)
        );
    }

    private function build(string $granularity): array
    {
        $buckets = $this->buckets($granularity);
        $since = Carbon::instance($buckets[0]['start']);
        $series = $this->series($buckets, $since);

        $registered = $this->repository->countVendors();
        $everContacted = $this->repository->countContactedVendors();

        return [
            'granularity' => $granularity,
            'range' => [
                'start' => $since->toDateString(),
                'end' => CarbonImmutable::now()->toDateString(),
            ],
            'series' => $series,
            'headline' => $this->headline($series),
            'funnel' => $this->funnel($registered, $everContacted),
            'supply' => $this->supplyHealth($registered, $everContacted),
            'revenue' => $this->revenue(),
            'unmet_demand' => $this->unmetDemand($since),
        ];
    }

    private function buckets(string $granularity): array
    {
        $isWeekly = $granularity === 'week';
        $count = $isWeekly ? self::WEEK_BUCKETS : self::MONTH_BUCKETS;
        $current = $isWeekly
            ? CarbonImmutable::now()->startOfWeek()
            : CarbonImmutable::now()->startOfMonth();

        $buckets = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $start = $isWeekly ? $current->subWeeks($i) : $current->subMonths($i);
            $end = $isWeekly ? $start->addWeek() : $start->addMonth();

            $buckets[] = [
                'key' => $start->toDateString(),
                'label' => $isWeekly ? $start->format('j M') : $start->format('M Y'),
                'start' => $start,
                'end' => $end,
                'is_partial' => $end->isFuture(),
            ];
        }

        return $buckets;
    }

    private function series(array $buckets, Carbon $since): array
    {
        $newVendors = $this->rollup($this->repository->newVendorsByDate($since), $buckets);
        $newCustomers = $this->rollup($this->repository->newCustomersByDate($since), $buckets);
        $newProducts = $this->rollup($this->repository->newProductsByDate($since), $buckets);
        $searches = $this->rollup(
            $this->repository->interactionsByDate(WhatsAppInteractionEventEnum::SEARCH, $since),
            $buckets
        );
        $contacts = $this->rollup(
            $this->repository->interactionsByDate(WhatsAppInteractionEventEnum::CONTACT_REQUESTED, $since),
            $buckets
        );
        $noResults = $this->rollup(
            $this->repository->interactionsByDate(WhatsAppInteractionEventEnum::NO_RESULTS, $since),
            $buckets
        );
        $newPaid = $this->rollup($this->repository->newSubscriptionsByDate($since), $buckets);
        $activeVendors = $this->activeVendorsPerBucket($buckets, $since);

        $totalVendors = $this->repository->vendorsBefore($since);
        $totalProducts = $this->repository->productsBefore($since);

        $rows = [];

        foreach ($buckets as $bucket) {
            $key = $bucket['key'];
            $totalVendors += $newVendors[$key];
            $totalProducts += $newProducts[$key];

            $rows[] = [
                'key' => $key,
                'label' => $bucket['label'],
                'is_partial' => $bucket['is_partial'],
                'new_vendors' => $newVendors[$key],
                'new_customers' => $newCustomers[$key],
                'new_products' => $newProducts[$key],
                'searches' => $searches[$key],
                'contacts' => $contacts[$key],
                'no_results' => $noResults[$key],
                'new_paid_subscriptions' => $newPaid[$key],
                'active_vendors' => $activeVendors[$key],
                'total_vendors' => $totalVendors,
                'total_products' => $totalProducts,
            ];
        }

        return $rows;
    }

    private function rollup(Collection $daily, array $buckets): array
    {
        $totals = array_fill_keys(array_column($buckets, 'key'), 0);

        foreach ($daily as $row) {
            $key = $this->bucketKeyFor((string) $row->date, $buckets);

            if ($key !== null) {
                $totals[$key] += (int) $row->count;
            }
        }

        return $totals;
    }

    private function activeVendorsPerBucket(array $buckets, Carbon $since): array
    {
        /** @var array<string, array<int, true>> $seen */
        $seen = array_fill_keys(array_column($buckets, 'key'), []);

        foreach ($this->repository->activeVendorDays($since) as $row) {
            $key = $this->bucketKeyFor((string) $row->date, $buckets);

            if ($key !== null) {
                $seen[$key][(int) $row->vendor_id] = true;
            }
        }

        return array_map(count(...), $seen);
    }

    private function bucketKeyFor(string $day, array $buckets): ?string
    {
        $date = CarbonImmutable::parse($day)->startOfDay();

        foreach ($buckets as $bucket) {
            if ($date >= $bucket['start'] && $date < $bucket['end']) {
                return $bucket['key'];
            }
        }

        return null;
    }

    private function headline(array $series): array
    {
        $complete = array_values(array_filter($series, static fn (array $row): bool => $row['is_partial'] === false));
        $partial = array_values(array_filter($series, static fn (array $row): bool => $row['is_partial'] === true));

        $latest = $complete[count($complete) - 1] ?? null;
        $previous = $complete[count($complete) - 2] ?? null;
        $inProgress = $partial[0] ?? null;

        $metrics = [];

        foreach (self::HEADLINE_METRICS as $metric) {
            $current = (int) ($latest[$metric] ?? 0);
            $prior = (int) ($previous[$metric] ?? 0);

            $metrics[$metric] = [
                'value' => $current,
                'previous' => $prior,
                'change_pct' => $prior > 0 ? round((($current - $prior) / $prior) * 100, 1) : null,
                'in_progress' => (int) ($inProgress[$metric] ?? 0),
            ];
        }

        return [
            'period_label' => $latest['label'] ?? null,
            'in_progress_label' => $inProgress['label'] ?? null,
            'metrics' => $metrics,
        ];
    }

    private function funnel(int $registered, int $everContacted): array
    {
        $steps = [
            ['label' => 'Registered as vendor', 'value' => $registered],
            ['label' => 'Onboarding approved', 'value' => $this->repository->countApprovedOnboardings()],
            ['label' => 'Listed a product', 'value' => $this->repository->countVendorsWithMinimumProducts(self::LISTED_THRESHOLD)],
            ['label' => 'Received a contact', 'value' => $everContacted],
        ];

        return array_map(
            static fn (array $step): array => [
                ...$step,
                'pct_of_registered' => $registered > 0 ? round(($step['value'] / $registered) * 100, 1) : 0.0,
            ],
            $steps
        );
    }

    private function supplyHealth(int $registered, int $everContacted): array
    {
        $now = CarbonImmutable::now();
        $activeFrom = $now->subDays(self::ACTIVE_WINDOW_DAYS);
        $priorFrom = $now->subDays(self::ACTIVE_WINDOW_DAYS * 2);

        return [
            'registered_vendors' => $registered,
            'active_last_30_days' => $this->repository->countContactedVendors(Carbon::instance($activeFrom)),
            'active_prior_30_days' => $this->repository->countContactedVendors(
                Carbon::instance($priorFrom),
                Carbon::instance($activeFrom)
            ),
            'ever_contacted' => $everContacted,
            'never_contacted' => max(0, $registered - $everContacted),
        ];
    }

    private function revenue(): array
    {
        $active = $this->repository->activeSubscriptionPeriods(Carbon::now());
        $monthlyRecurring = 0.0;

        foreach ($active as $subscription) {
            $days = CarbonImmutable::parse($subscription->started_at)
                ->diffInDays(CarbonImmutable::parse($subscription->expires_at));
            $months = max(1.0, $days / self::DAYS_PER_MONTH);
            $monthlyRecurring += (float) ($subscription->amount_paid ?? 0) / $months;
        }

        $payingVendors = $active->count();

        return [
            'paying_vendors' => $payingVendors,
            'monthly_recurring' => round($monthlyRecurring, 2),
            'average_per_vendor' => $payingVendors > 0 ? round($monthlyRecurring / $payingVendors, 2) : 0.0,
            'lifetime_collected' => $this->repository->lifetimeCollected(),
        ];
    }

    private function unmetDemand(Carbon $since): array
    {
        return $this->repository->topUnmetSearches($since, self::UNMET_DEMAND_LIMIT)
            ->map(static fn (object $row): array => [
                'query' => (string) $row->search_query,
                'total' => (int) $row->count,
            ])
            ->all();
    }
}
