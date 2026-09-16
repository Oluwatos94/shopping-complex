<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\LeadCreditReasonEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

final class LeadFailureDetector
{
    public function reasonFor(BillableLead $lead): ?LeadCreditReasonEnum
    {
        if ($this->isDuplicate($lead)) {
            return LeadCreditReasonEnum::DUPLICATE;
        }

        if ($this->hasUnreachableNumber($lead)) {
            return LeadCreditReasonEnum::INVALID_NUMBER;
        }

        if ($this->isFlagged($lead)) {
            return LeadCreditReasonEnum::FLAGGED;
        }

        return null;
    }

    private function isDuplicate(BillableLead $lead): bool
    {
        return BillableLead::where('vendor_id', $lead->vendor_id)
            ->where('buyer_identity', $lead->buyer_identity)
            ->where('state', BillableLeadStateEnum::CHARGED)
            ->where('id', '<', $lead->id)
            ->where('window_start', '>', $lead->window_start->copy()->subDays(LeadBillingService::WINDOW_DAYS))
            ->exists();
    }

    private function hasUnreachableNumber(BillableLead $lead): bool
    {
        $vendor = $lead->vendor;

        return $vendor === null || WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? '')) === null;
    }

    private function isFlagged(BillableLead $lead): bool
    {
        $click = $lead->openingClick;

        if ($click === null || $click->buyer_identity === null) {
            return false;
        }

        $buyerMinutes = (int) config('billing.guards.velocity_buyer.minutes', 5);
        $vendors = ContactClick::query()
            ->where('buyer_identity', $click->buyer_identity)
            ->where('is_billable', true)
            ->whereBetween('created_at', [$click->created_at->copy()->subMinutes($buyerMinutes), $click->created_at->copy()->addMinutes($buyerMinutes)])
            ->distinct()
            ->count('vendor_id');

        if ($vendors >= (int) config('billing.guards.velocity_buyer.vendors', 4)) {
            return true;
        }

        if ($click->ip_address === null) {
            return false;
        }

        $ipMinutes = (int) config('billing.guards.velocity_ip.minutes', 10);
        $identities = ContactClick::query()
            ->where('ip_address', $click->ip_address)
            ->where('is_billable', true)
            ->whereNotNull('buyer_identity')
            ->whereBetween('created_at', [$click->created_at->copy()->subMinutes($ipMinutes), $click->created_at->copy()->addMinutes($ipMinutes)])
            ->distinct()
            ->count('buyer_identity');

        return $identities >= (int) config('billing.guards.velocity_ip.identities', 6);
    }
}
