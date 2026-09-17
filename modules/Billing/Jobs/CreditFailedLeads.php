<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\LeadCreditService;
use ModulesShoppingComplex\Billing\Services\LeadFailureDetector;

class CreditFailedLeads implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function handle(LeadFailureDetector $detector, LeadCreditService $credits): void
    {
        $lookbackDays = (int) config('billing.credits.lookback_days', 35);
        $since = now()->subDays($lookbackDays);
        $creditedThisRun = 0;

        BillableLead::where('state', BillableLeadStateEnum::CHARGED)
            ->whereNull('credit_reason')
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->chunkById(200, function ($leads) use ($detector, $credits, &$creditedThisRun): void {
                foreach ($leads as $lead) {
                    $reason = $detector->reasonFor($lead);

                    if ($reason !== null && $credits->credit($lead, $reason)) {
                        $creditedThisRun++;
                        Log::info('Auto-credited lead for delivery failure', [
                            'lead_id' => $lead->id,
                            'vendor_id' => $lead->vendor_id,
                            'reason' => $reason->value,
                            'coins' => $lead->coins_charged,
                        ]);
                    }
                }
            });

        $rate = $credits->creditRate($since, now());

        Log::info('Lead credit rate', [
            'window_days' => $lookbackDays,
            'rate' => round($rate, 4),
            'credited_this_run' => $creditedThisRun,
        ]);

        if ($rate > (float) config('billing.credits.alert_rate', 0.05)) {
            Log::warning('Lead credit rate above threshold — check the delivery path, not the policy', [
                'rate' => round($rate, 4),
            ]);
        }
    }
}
