<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Events\VendorLeadDeclined;
use ModulesShoppingComplex\Billing\Events\VendorLeadExpired;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;

/**
 * Tells the buyer how their request ended. Only on acceptance do they get the
 * vendor's WhatsApp link — the vendor has paid for the introduction by then.
 *
 * These are free-form messages, which WhatsApp only delivers inside the 24h
 * customer-service window opened by the buyer's last message; that is why
 * billing.leads.accept_window_hours defaults to well under 24.
 */
class NotifyBuyerOfLeadOutcome implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly ContactLinkService $links,
        private readonly WhatsAppSender $whatsApp,
    ) {}

    public function handle(VendorLeadCharged|VendorLeadDeclined|VendorLeadExpired $event): void
    {
        $lead = $event->lead;

        // Click-mode charges have no buyer waiting for an answer.
        if ($event instanceof VendorLeadCharged && ! $lead->wasAccepted()) {
            return;
        }

        $buyer = (string) preg_replace('/\D/', '', $lead->buyer_identity);
        $vendor = User::find($lead->vendor_id);

        if ($buyer === '' || $vendor === null) {
            return;
        }

        $name = $vendor->business_name ?? $vendor->name;

        $message = match (true) {
            $event instanceof VendorLeadCharged => $this->acceptedMessage($vendor, $name),
            $event instanceof VendorLeadDeclined => "*{$name}* can't take your request right now. Tell me what you're looking for and I'll find other vendors near you.",
            default => "*{$name}* hasn't responded yet, so I've closed your request. Tell me what you're looking for and I'll find other vendors near you.",
        };

        if ($message !== null) {
            $this->whatsApp->sendText($buyer, $message);
        }
    }

    private function acceptedMessage(User $vendor, string $name): ?string
    {
        $whatsApp = $this->links->destinationFor($vendor);

        if ($whatsApp === null) {
            return null;
        }

        $lines = ["✅ *{$name}* accepted your request!", "Chat with them on WhatsApp: {$whatsApp}"];

        if ($vendor->slug) {
            $lines[] = 'Profile: '.url('/vendors/'.$vendor->slug);
        }

        return implode("\n", $lines);
    }
}
