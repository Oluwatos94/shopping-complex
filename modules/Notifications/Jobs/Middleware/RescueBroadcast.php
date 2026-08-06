<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

final class RescueBroadcast
{
    public function handle(object $job, Closure $next): void
    {
        try {
            $next($job);
        } catch (\Throwable $e) {
            Log::warning('Notification broadcast skipped', [
                'job' => $job::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
