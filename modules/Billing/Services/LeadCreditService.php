<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\LeadCreditReasonEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;

final class LeadCreditService
{
    public function __construct(
        private readonly CoinWalletService $wallet,
    ) {}

    public function credit(BillableLead $lead, LeadCreditReasonEnum $reason): bool
    {
        if ($lead->state !== BillableLeadStateEnum::CHARGED || $lead->coins_charged <= 0 || $lead->credit_reason !== null) {
            return false;
        }

        $vendor = $lead->vendor;

        if ($vendor === null) {
            return false;
        }

        return DB::transaction(function () use ($lead, $reason, $vendor): bool {
            if ($this->wallet->refund($vendor, $lead)->isEmpty()) {
                return false;
            }

            $lead->forceFill(['credit_reason' => $reason])->save();

            return true;
        });
    }

    public function creditRate(CarbonInterface $from, CarbonInterface $to): float
    {
        $billed = BillableLead::where('state', BillableLeadStateEnum::CHARGED)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        if ($billed === 0) {
            return 0.0;
        }

        $credited = BillableLead::credited()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return $credited / $billed;
    }
}
