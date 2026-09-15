<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Billing\Contracts\LeadDebitor;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Identity\Models\User;

final class LeadBillingService
{
    public const WINDOW_DAYS = 30;

    public function __construct(
        private readonly LeadDebitor $debitor,
        private readonly LeadPricingService $pricing,
    ) {}

    public function bill(ContactClick $click): ?BillableLead
    {
        if (! $click->is_billable || $click->buyer_identity === null) {
            return null;
        }

        return DB::transaction(function () use ($click): ?BillableLead {
            $vendor = User::whereKey($click->vendor_id)->lockForUpdate()->first();

            if ($vendor === null) {
                return null;
            }

            $open = $this->openLeadFor($click);

            if ($open !== null) {
                return $this->recordRepeat($open, $click);
            }

            $lead = BillableLead::create([
                'contact_click_id' => $click->id,
                'vendor_id' => $click->vendor_id,
                'buyer_identity' => $click->buyer_identity,
                'channel' => $click->source,
                'coins_charged' => 0,
                'state' => BillableLeadStateEnum::UNBILLED,
                'window_start' => now(),
                'repeat_count' => 0,
                'last_click_at' => now(),
            ]);

            $charged = $this->debitor->debit($vendor, $lead, $this->pricing->costFor($vendor));

            if ($charged > 0) {
                $lead->forceFill([
                    'coins_charged' => $charged,
                    'state' => BillableLeadStateEnum::CHARGED,
                ])->save();
            }

            return $lead;
        });
    }

    private function openLeadFor(ContactClick $click): ?BillableLead
    {
        return BillableLead::where('buyer_identity', $click->buyer_identity)
            ->where('vendor_id', $click->vendor_id)
            ->where('window_start', '>', now()->subDays(self::WINDOW_DAYS))
            ->lockForUpdate()
            ->first();
    }

    private function recordRepeat(BillableLead $lead, ContactClick $click): BillableLead
    {
        if ($lead->contact_click_id === $click->id) {
            return $lead;
        }

        $lead->forceFill([
            'repeat_count' => $lead->repeat_count + 1,
            'last_click_at' => now(),
        ])->save();

        $click->forceFill(['is_billable' => false])->save();

        return $lead;
    }
}
