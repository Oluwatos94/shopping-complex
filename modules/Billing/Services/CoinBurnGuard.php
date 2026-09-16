<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

/**
 * Decides whether a lead may be billed, or must be withheld because it looks
 * like coin-burning: a vendor clicking their own link, a buyer or IP fanning
 * out across many targets, or the vendor's own daily cap being reached.
 */
final class CoinBurnGuard
{
    /**
     * The reason this lead must not be billed, or null when it may be charged.
     * $chargedToday is the vendor's coins already spent today, locked by the caller.
     */
    public function blockReason(ContactClick $click, User $vendor, int $attemptedCost, int $chargedToday): ?LeadUnbilledReasonEnum
    {
        if ($this->isSelfClick($click, $vendor)) {
            return LeadUnbilledReasonEnum::SELF_CLICK;
        }

        if ($this->buyerFansOut($click)) {
            return LeadUnbilledReasonEnum::VELOCITY_BUYER;
        }

        if ($this->ipFansOut($click)) {
            return LeadUnbilledReasonEnum::VELOCITY_IP;
        }

        if ($this->capReached($vendor, $attemptedCost, $chargedToday)) {
            return LeadUnbilledReasonEnum::DAILY_CAP;
        }

        return null;
    }

    private function isSelfClick(ContactClick $click, User $vendor): bool
    {
        $vendorPhone = WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? ''));
        $buyerPhone = WhatsAppPhone::toE164((string) ($click->buyer_identity ?? ''));

        return $vendorPhone !== null && $vendorPhone === $buyerPhone;
    }

    private function buyerFansOut(ContactClick $click): bool
    {
        $threshold = (int) config('billing.guards.velocity_buyer.vendors', 4);
        $minutes = (int) config('billing.guards.velocity_buyer.minutes', 5);

        $vendors = ContactClick::query()
            ->where('buyer_identity', $click->buyer_identity)
            ->where('is_billable', true)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->distinct()
            ->count('vendor_id');

        return $vendors >= $threshold;
    }

    private function ipFansOut(ContactClick $click): bool
    {
        if ($click->ip_address === null) {
            return false;
        }

        $threshold = (int) config('billing.guards.velocity_ip.identities', 6);
        $minutes = (int) config('billing.guards.velocity_ip.minutes', 10);

        $identities = ContactClick::query()
            ->where('ip_address', $click->ip_address)
            ->where('is_billable', true)
            ->whereNotNull('buyer_identity')
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->distinct()
            ->count('buyer_identity');

        return $identities >= $threshold;
    }

    private function capReached(User $vendor, int $attemptedCost, int $chargedToday): bool
    {
        $cap = $vendor->daily_coin_cap;

        return $cap !== null && $chargedToday + $attemptedCost > $cap;
    }
}
