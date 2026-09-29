<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\LeadBuyerContextResolver;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;
use ModulesShoppingComplex\Notifications\Services\NotificationEmailService;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
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
        private readonly LeadBuyerContextResolver $context,
    ) {}

    public function handle(VendorLeadCharged $event): void
    {
        $lead = $event->lead;

        // Accept mode: the vendor just accepted this lead themselves and got the buyer's
        // contact in reply (WhatsApp) or on the Leads page, so a "new lead" alert is noise.
        if ($lead->wasAccepted()) {
            return;
        }

        $vendor = User::find($lead->vendor_id);

        if ($vendor === null) {
            return;
        }

        [$search, $area] = $this->context->resolveAndStore($lead);
        $balance = $this->wallet->balance($vendor);

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
}
