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
use ModulesShoppingComplex\Billing\Services\LeadAcceptanceService;

/**
 * Closes lead requests the vendor did not answer in time, so the buyer hears back.
 * Scheduled in routes/console.php — it only runs if the scheduler process runs.
 */
class ExpirePendingLeads implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 900;

    public function handle(LeadAcceptanceService $leads): void
    {
        $expired = $leads->expireOverdue();

        if ($expired > 0) {
            Log::info('Expired unanswered lead requests', ['count' => $expired]);
        }
    }
}
