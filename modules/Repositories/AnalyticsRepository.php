<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;

class AnalyticsRepository extends BasePageRepository
{
    public function getChatContactCount(int $vendorId, Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('conversations')
            ->where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get chat contacts grouped by date.
     *
     * @return Collection<int, \stdClass>
     */
    public function getChatContactsByDate(int $vendorId, Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('conversations')
            ->where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    public function getProfileViewCount(int $vendorId, Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('profile_views')
            ->where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get profile views grouped by date.
     *
     * @return Collection<int, \stdClass>
     */
    public function getProfileViewsByDate(int $vendorId, Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('profile_views')
            ->where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    public function getProductViewCount(int $vendorId, Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('product_views')
            ->where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get top viewed products for a vendor in a date range.
     *
     * @return Collection<int, \stdClass>
     */
    public function getTopViewedProducts(int $vendorId, Carbon $startDate, Carbon $endDate, int $limit = 10): Collection
    {
        return DB::table('product_views')
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->where('product_views.vendor_id', $vendorId)
            ->whereBetween('product_views.created_at', [$startDate, $endDate])
            ->whereNull('products.deleted_at')
            ->selectRaw('products.id as product_id, products.name, products.price, COUNT(*) as views_count')
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    public function getAverageViewValue(int $vendorId, Carbon $startDate, Carbon $endDate): float
    {
        $subquery = DB::table('product_views')
            ->join('products', 'product_views.product_id', '=', 'products.id')
            ->where('product_views.vendor_id', $vendorId)
            ->whereBetween('product_views.created_at', [$startDate, $endDate])
            ->whereNull('products.deleted_at')
            ->select('products.id', 'products.price')
            ->distinct();

        $result = DB::query()
            ->selectRaw('AVG(sub.price) as avg_value')
            ->fromSub($subquery, 'sub')
            ->value('avg_value');

        return round((float) ($result ?? 0), 2);
    }

    public function getFollowerCount(int $vendorId): int
    {
        return DB::table('vendor_followers')
            ->where('vendor_id', $vendorId)
            ->count();
    }

    public function getActiveProductCount(int $vendorId): int
    {
        return DB::table('products')
            ->where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();
    }

    public function getWhatsAppSearchAppearances(int $vendorId, Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('whatsapp_interactions')
            ->where('vendor_id', $vendorId)
            ->where('event_type', WhatsAppInteractionEventEnum::VENDOR_VIEWED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    public function getWhatsAppCatalogueViews(int $vendorId, Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('whatsapp_interactions')
            ->where('vendor_id', $vendorId)
            ->where('event_type', WhatsAppInteractionEventEnum::PRODUCT_CATALOGUE_VIEWED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    public function getWhatsAppContactRequests(int $vendorId, Carbon $startDate, Carbon $endDate): int
    {
        return DB::table('whatsapp_interactions')
            ->where('vendor_id', $vendorId)
            ->where('event_type', WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get daily WhatsApp search appearances for chart rendering.
     *
     * @return Collection<int, \stdClass>
     */
    public function getWhatsAppSearchAppearancesByDate(int $vendorId, Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('whatsapp_interactions')
            ->where('vendor_id', $vendorId)
            ->where('event_type', WhatsAppInteractionEventEnum::VENDOR_VIEWED->value)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top search terms that triggered this vendor appearing in WhatsApp results.
     *
     * @return Collection<int, \stdClass>
     */
    public function getTopWhatsAppSearchQueries(int $vendorId, Carbon $startDate, Carbon $endDate, int $limit = 10): Collection
    {
        return DB::table('whatsapp_interactions')
            ->where('vendor_id', $vendorId)
            ->where('event_type', WhatsAppInteractionEventEnum::VENDOR_VIEWED->value)
            ->whereNotNull('search_query')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('search_query, COUNT(*) as count')
            ->groupBy('search_query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    /**
     * Break down profile views by source (web vs whatsapp) for this vendor.
     *
     * @return Collection<int, \stdClass>
     */
    public function getProfileViewsBySource(int $vendorId, Carbon $startDate, Carbon $endDate): Collection
    {
        return DB::table('profile_views')
            ->where('vendor_id', $vendorId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderBy('source')
            ->get();
    }
}
