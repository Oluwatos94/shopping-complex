<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param  string  $to  Recipient phone number (E.164 format)
     * @param  array<string, mixed>  $payload  Meta Cloud API message payload
     */
    public function __construct(
        public readonly string $to,
        public readonly array $payload,
    ) {}

    public function handle(): void
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $accessToken = config('services.whatsapp.access_token');

        $response = Http::withToken((string) $accessToken)
            ->post("https://graph.facebook.com/v19.0/{$phoneNumberId}/messages", $this->payload);

        if (! $response->successful()) {
            // Throwing causes Laravel to retry with $backoff delay, exhausts $tries, then calls failed().
            // Using $this->release() is incorrect — it re-queues without decrementing tries reliably.
            throw new \RuntimeException(
                "WhatsApp API error {$response->status()}: {$response->body()}"
            );
        }
    }

    /**
     * Handle final failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp message permanently failed after all retries', [
            'to' => '***'.substr($this->to, -4), // mask PII — only log last 4 digits
            'error' => $exception->getMessage(),
        ]);
    }
}
