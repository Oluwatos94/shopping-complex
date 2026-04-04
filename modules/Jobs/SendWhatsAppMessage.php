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
            Log::warning('WhatsApp message send failed', [
                'to' => $this->to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            $this->release(10);
        }
    }

    /**
     * Handle final failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('WhatsApp message permanently failed after all retries', [
            'to' => $this->to,
            'error' => $exception->getMessage(),
        ]);
    }
}
