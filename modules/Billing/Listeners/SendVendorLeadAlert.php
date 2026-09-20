<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Discovery\Services\GeoLocationService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;
use ModulesShoppingComplex\Notifications\Services\NotificationEmailService;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\WhatsApp\Models\WhatsAppInteraction;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

/**
 * The vendor's receipt for a charged lead: a WhatsApp Utility template when they
 * have a usable number, an emailed fallback when they don't, and always an in-app
 * notification. Queued, so a template failure retries and never touches the debit
 * or the buyer's redirect.
 */
class SendVendorLeadAlert implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 30;

    private const TYPE = 'lead_alert';

    public function __construct(
        private readonly CoinWalletService $wallet,
        private readonly NotificationRepository $notifications,
        private readonly NotificationEmailService $emailFallback,
        private readonly WhatsAppSender $whatsApp,
        private readonly GeoLocationService $geo,
    ) {}

    public function handle(VendorLeadCharged $event): void
    {
        $lead = $event->lead;
        $vendor = User::find($lead->vendor_id);

        if ($vendor === null) {
            return;
        }

        [$search, $area] = $this->buyerContext($lead);
        $balance = $this->wallet->balance($vendor);

        $lead->forceFill([
            'buyer_search' => $search === 'a product or service' ? null : $search,
            'buyer_area' => $area === 'your area' ? null : $area,
        ])->save();

        $notification = $this->recordInApp($lead, $vendor, $search, $area, $balance);

        $to = WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? ''));

        if ($to === null) {
            $this->emailFallback->queueFallbackEmail($notification);

            return;
        }

        // Thrown failures retry via ShouldQueue; the in-app record already exists.
        $this->whatsApp->sendTemplate(
            $to,
            (string) config('services.whatsapp.templates.lead_alert'),
            (string) config('services.whatsapp.template_language', 'en'),
            [[
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $text) => ['type' => 'text', 'text' => $text],
                    [
                        $vendor->business_name ?? $vendor->name,
                        $search,
                        $area,
                        (string) $lead->coins_charged,
                        (string) $balance,
                    ],
                ),
            ]],
        );
    }

    /**
     * Create the in-app record once — a retry must not add a second one.
     */
    private function recordInApp(BillableLead $lead, User $vendor, string $search, string $area, int $balance): Notification
    {
        $existing = Notification::where('user_id', $vendor->id)
            ->where('type', self::TYPE)
            ->where('data->lead_id', $lead->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->notifications->create([
            'user_id' => $vendor->id,
            'type' => self::TYPE,
            'message' => sprintf(
                'New lead: a buyer near %s searched for %s and requested your contact. %d coins charged — balance %d.',
                $area,
                $search,
                $lead->coins_charged,
                $balance,
            ),
            'data' => [
                'action' => 'lead_alert',
                'lead_id' => $lead->id,
                'coins_charged' => $lead->coins_charged,
                'balance' => $balance,
            ],
        ]);
    }

    /**
     * The buyer's last search before this contact, and a rough area for it.
     * Best-effort: web leads and buyers with no logged search fall back to
     * neutral wording so no template parameter is ever empty.
     *
     * @return array{0: string, 1: string}
     */
    private function buyerContext(BillableLead $lead): array
    {
        $search = 'a product or service';
        $area = 'your area';

        $interaction = WhatsAppInteraction::query()
            ->where('phone_number', $lead->buyer_identity)
            ->where('event_type', WhatsAppInteractionEventEnum::VENDOR_VIEWED->value)
            ->where('vendor_id', $lead->vendor_id)
            ->where('created_at', '<=', $lead->created_at)
            ->latest('id')
            ->first();

        if ($interaction === null) {
            return [$search, $area];
        }

        if (! empty($interaction->search_query)) {
            $search = (string) $interaction->search_query;
        }

        if ($interaction->buyer_latitude !== null && $interaction->buyer_longitude !== null) {
            try {
                $label = $this->geo->reverseGeocode($interaction->buyer_latitude, $interaction->buyer_longitude);
                if ($label !== null && $label !== '') {
                    $area = $label;
                }
            } catch (\Throwable $e) {
                Log::warning('Lead alert reverse-geocode failed', ['lead_id' => $lead->id, 'error' => $e->getMessage()]);
            }
        }

        return [$search, $area];
    }
}
