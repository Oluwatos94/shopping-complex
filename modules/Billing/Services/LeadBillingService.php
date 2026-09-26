<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Billing\Contracts\LeadDebitor;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Events\VendorLeadMissed;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

final class LeadBillingService
{
    public const WINDOW_DAYS = 30;

    public function __construct(
        private readonly LeadDebitor $debitor,
        private readonly LeadPricingService $pricing,
        private readonly CoinBurnGuard $guard,
    ) {}

    public function bill(ContactClick $click): ?BillableLead
    {
        if (! $click->is_billable || $click->buyer_identity === null) {
            return null;
        }

        $wasCharged = false;
        $wasMissed = false;
        $attemptedCost = 0;

        $lead = DB::transaction(function () use ($click, &$wasCharged, &$wasMissed, &$attemptedCost): ?BillableLead {
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
                'delivered_number' => WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? '')),
                'window_start' => now(),
                'repeat_count' => 0,
                'last_click_at' => now(),
            ]);

            $attemptedCost = $this->pricing->costFor($vendor);

            $blockReason = $this->guard->blockReason($click, $vendor, $attemptedCost, $this->chargedToday($vendor));

            if ($blockReason !== null) {
                $lead->forceFill(['unbilled_reason' => $blockReason])->save();

                return $lead;
            }

            $charged = $this->debitor->debit($vendor, $lead, $attemptedCost);

            if ($charged > 0) {
                $lead->forceFill([
                    'coins_charged' => $charged,
                    'state' => BillableLeadStateEnum::CHARGED,
                ])->save();

                $wasCharged = true;
            } else {
                $lead->forceFill(['unbilled_reason' => LeadUnbilledReasonEnum::INSUFFICIENT_BALANCE])->save();

                $wasMissed = true;
            }

            return $lead;
        });

        if ($wasCharged && $lead !== null) {
            VendorLeadCharged::dispatch($lead);
        }

        if ($wasMissed && $lead !== null) {
            VendorLeadMissed::dispatch($lead, $attemptedCost);
        }

        return $lead;
    }

    private function chargedToday(User $vendor): int
    {
        return (int) BillableLead::where('vendor_id', $vendor->id)
            ->where('state', BillableLeadStateEnum::CHARGED)
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('coins_charged');
    }

    private function openLeadFor(ContactClick $click): ?BillableLead
    {
        $lead = $this->openLeadForIdentity($click, (string) $click->buyer_identity);

        if ($lead !== null || ! str_starts_with((string) $click->buyer_identity, 'visitor_')) {
            return $lead;
        }

        // A first hit without a cookie is billed under the IP + user-agent hash;
        // when that browser returns with its cookie, carry the lead over.
        $lead = $this->openLeadForIdentity($click, ContactLinkService::anonymousIdentity($click->ip_address, $click->user_agent));
        $lead?->forceFill(['buyer_identity' => $click->buyer_identity])->save();

        return $lead;
    }

    private function openLeadForIdentity(ContactClick $click, string $identity): ?BillableLead
    {
        return BillableLead::where('buyer_identity', $identity)
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
