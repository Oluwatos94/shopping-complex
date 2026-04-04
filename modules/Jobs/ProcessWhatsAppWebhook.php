<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWhatsAppWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $payload  Raw Meta webhook payload
     */
    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        // Implementation in #123 — Bot background jobs task
    }
}
