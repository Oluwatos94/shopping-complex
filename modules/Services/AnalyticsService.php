<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Models\ProductView;
use ModulesShoppingComplex\Models\ProfileView;
use ModulesShoppingComplex\Repositories\AnalyticsRepository;

final readonly class AnalyticsService
{
    private const ALLOWED_PERIODS = ['daily', 'weekly', 'monthly', 'yearly'];

    private const MAX_DATE_RANGE_DAYS = 365;

    public function __construct(
        private AnalyticsRepository $analyticsRepository
    ) {}

    /**
     * Get overview dashboard statistics.
     *
     * @return array<string, mixed>
     */
    public function getOverview(int $vendorId, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'chat_contacts' => $this->analyticsRepository->getChatContactCount($vendorId, $startDate, $endDate),
            'profile_views' => $this->analyticsRepository->getProfileViewCount($vendorId, $startDate, $endDate),
            'product_views' => $this->analyticsRepository->getProductViewCount($vendorId, $startDate, $endDate),
            'average_view_value' => $this->analyticsRepository->getAverageViewValue($vendorId, $startDate, $endDate),
            'followers_count' => $this->analyticsRepository->getFollowerCount($vendorId),
            'active_products' => $this->analyticsRepository->getActiveProductCount($vendorId),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get chat contact metrics with daily breakdown.
     *
     * @return array<string, mixed>
     */
    public function getChatContactMetrics(int $vendorId, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total' => $this->analyticsRepository->getChatContactCount($vendorId, $startDate, $endDate),
            'daily' => $this->analyticsRepository->getChatContactsByDate($vendorId, $startDate, $endDate),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get profile view metrics with daily breakdown.
     *
     * @return array<string, mixed>
     */
    public function getProfileViewMetrics(int $vendorId, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'total' => $this->analyticsRepository->getProfileViewCount($vendorId, $startDate, $endDate),
            'daily' => $this->analyticsRepository->getProfileViewsByDate($vendorId, $startDate, $endDate),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get top viewed products.
     *
     * @return array<string, mixed>
     */
    public function getTopProducts(int $vendorId, Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return [
            'products' => $this->analyticsRepository->getTopViewedProducts($vendorId, $startDate, $endDate, $limit),
            'average_view_value' => $this->analyticsRepository->getAverageViewValue($vendorId, $startDate, $endDate),
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Record a profile view with deduplication (one per viewer/IP per day).
     */
    public function recordProfileView(int $vendorId, ?int $viewerId, ?string $ipAddress): void
    {
        try {
            $query = ProfileView::where('vendor_id', $vendorId)
                ->where('created_at', '>=', now()->startOfDay());

            if ($viewerId) {
                $query->where('viewer_id', $viewerId);
            } else {
                $query->whereNull('viewer_id')->where('ip_address', $ipAddress);
            }

            if (! $query->exists()) {
                ProfileView::create([
                    'vendor_id' => $vendorId,
                    'viewer_id' => $viewerId,
                    'ip_address' => $ipAddress,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to record profile view', ['vendor_id' => $vendorId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Record a product view with deduplication (one per viewer/IP per day per product).
     */
    public function recordProductView(int $productId, int $vendorId, ?int $viewerId, ?string $ipAddress): void
    {
        try {
            $query = ProductView::where('product_id', $productId)
                ->where('vendor_id', $vendorId)
                ->where('created_at', '>=', now()->startOfDay());

            if ($viewerId) {
                $query->where('viewer_id', $viewerId);
            } else {
                $query->whereNull('viewer_id')->where('ip_address', $ipAddress);
            }

            if (! $query->exists()) {
                ProductView::create([
                    'product_id' => $productId,
                    'vendor_id' => $vendorId,
                    'viewer_id' => $viewerId,
                    'ip_address' => $ipAddress,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to record product view', ['product_id' => $productId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Resolve period parameter to date range.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolveDateRange(?string $period, ?string $startDate, ?string $endDate): array
    {
        if ($startDate && $endDate) {
            try {
                $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
                $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();
            } catch (\Throwable) {
                return $this->defaultDateRange();
            }

            // Ensure start <= end
            if ($start->greaterThan($end)) {
                return $this->defaultDateRange();
            }

            // Cap range to max allowed days
            if ($start->diffInDays($end) > self::MAX_DATE_RANGE_DAYS) {
                $start = $end->copy()->subDays(self::MAX_DATE_RANGE_DAYS)->startOfDay();
            }

            return [$start, $end];
        }

        if ($period && ! in_array($period, self::ALLOWED_PERIODS, true)) {
            return $this->defaultDateRange();
        }

        $end = now()->endOfDay();

        return match ($period) {
            'daily' => [now()->startOfDay(), $end],
            'weekly' => [now()->subWeek()->startOfDay(), $end],
            'yearly' => [now()->subYear()->startOfDay(), $end],
            default => [now()->subMonth()->startOfDay(), $end],
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function defaultDateRange(): array
    {
        return [now()->subMonth()->startOfDay(), now()->endOfDay()];
    }
}
