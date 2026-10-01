<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Services\LeadAcceptanceService;
use ModulesShoppingComplex\WhatsApp\Services\WhatsAppAiBotService;

class ProcessWhatsAppWebhook implements ShouldBeUnique, ShouldQueue
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

    /**
     * Deduplicate on Meta's message ID so retries don't send the buyer the same reply twice.
     * Delivery receipts and status updates have no message ID — uniqueId returns '' and
     * ShouldBeUnique does not enforce uniqueness for empty strings.
     */
    public function uniqueId(): string
    {
        return (string) data_get($this->payload, 'entry.0.changes.0.value.messages.0.id', '');
    }

    public function handle(WhatsAppAiBotService $botService, LeadAcceptanceService $leads): void
    {
        /** @var array<string, mixed>|null $status */
        $status = data_get($this->payload, 'entry.0.changes.0.value.statuses.0');
        if (is_array($status) && ($status['status'] ?? '') === 'failed') {
            Log::warning('WhatsApp delivery failed', [
                'recipient' => '***'.substr((string) ($status['recipient_id'] ?? ''), -4),
                'errors' => $status['errors'] ?? null,
            ]);
        }

        /** @var array<string, mixed>|null $message */
        $message = data_get($this->payload, 'entry.0.changes.0.value.messages.0');

        if (! is_array($message)) {
            return; // delivery receipt or status update — nothing to process
        }

        $from = (string) ($message['from'] ?? '');
        $messageType = (string) ($message['type'] ?? '');

        if ($from === '' || $messageType === '') {
            return;
        }

        $messageBody = match ($messageType) {
            'text' => (string) ($message['text']['body'] ?? ''),
            'interactive' => (string) (
                $message['interactive']['list_reply']['id']
                ?? $message['interactive']['button_reply']['id']
                ?? ''
            ),
            // Quick-reply button on a template (e.g. Accept / Decline on lead_request).
            'button' => (string) ($message['button']['payload'] ?? $message['button']['text'] ?? ''),
            default => null,
        };

        // A vendor answering a lead request is not a buyer conversation.
        if (in_array($messageType, ['button', 'interactive'], true)
            && is_string($messageBody)
            && $leads->respondFromWhatsApp($from, $messageBody)) {
            return;
        }

        $botService->handle($from, $messageType, $messageBody, $message);
    }
}
