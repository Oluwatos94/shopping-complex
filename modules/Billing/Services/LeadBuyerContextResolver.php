<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Discovery\Services\GeoLocationService;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\WhatsApp\Models\WhatsAppInteraction;

/**
 * Works out what a buyer was looking for and roughly where, from the bot search
 * that surfaced the vendor. Used to tell the vendor what the lead is about.
 */
final class LeadBuyerContextResolver
{
    public const DEFAULT_SEARCH = 'a product or service';

    public const DEFAULT_AREA = 'your area';

    public function __construct(
        private readonly GeoLocationService $geo,
    ) {}

    /**
     * @return array{0: string, 1: string} [search, area], falling back to the defaults
     */
    public function resolve(BillableLead $lead): array
    {
        $search = self::DEFAULT_SEARCH;
        $area = self::DEFAULT_AREA;

        $interaction = WhatsAppInteraction::query()
            ->where('phone_number', $lead->buyer_identity)
            ->where('event_type', WhatsAppInteractionEventEnum::VENDOR_VIEWED->value)
            ->where('vendor_id', $lead->vendor_id)
            ->where('created_at', '<=', $lead->created_at)
            ->latest('id')
            ->first();

        if ($interaction === null) {
            return [$search, $area];
        }

        if (! empty($interaction->search_query)) {
            $search = (string) $interaction->search_query;
        }

        if ($interaction->buyer_latitude !== null && $interaction->buyer_longitude !== null) {
            try {
                $label = $this->geo->reverseGeocode($interaction->buyer_latitude, $interaction->buyer_longitude);
                if ($label !== null && $label !== '') {
                    $area = $label;
                }
            } catch (\Throwable $e) {
                Log::warning('Lead alert reverse-geocode failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            }
        }

        return [$search, $area];
    }

    /**
     * Resolve and store the context on the lead, keeping the columns null when only
     * the generic defaults are known.
     *
     * @return array{0: string, 1: string}
     */
    public function resolveAndStore(BillableLead $lead): array
    {
        [$search, $area] = $this->resolve($lead);

        $lead->forceFill([
            'buyer_search' => $search === self::DEFAULT_SEARCH ? null : mb_substr($search, 0, 120),
            'buyer_area' => $area === self::DEFAULT_AREA ? null : mb_substr($area, 0, 120),
        ])->save();

        return [$search, $area];
    }
}
