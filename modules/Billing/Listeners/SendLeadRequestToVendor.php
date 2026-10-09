<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\VendorLeadRequested;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\LeadAcceptanceService;
use ModulesShoppingComplex\Billing\Services\LeadBuyerContextResolver;
use ModulesShoppingComplex\Billing\Services\LeadPricingService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;
use ModulesShoppingComplex\Notifications\Services\NotificationEmailService;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;

/**
 * Asks the vendor to accept a new lead request: an in-app notification plus the
 * `lead_request` WhatsApp template, whose two quick-reply buttons carry
 * LEAD_ACCEPT:<id> / LEAD_DECLINE:<id> back to our webhook.
 *
 * The buyer's number is never included — the vendor gets it only after accepting.
 */
class SendLeadRequestToVendor implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 30;

    private const TYPE = 'lead_request';

    public function __construct(
        private readonly CoinWalletService $wallet,
        private readonly LeadPricingService $pricing,
        private readonly NotificationRepository $notifications,
        private readonly NotificationEmailService $emailFallback,
        private readonly WhatsAppSender $whatsApp,
    ) {}

    public function handle(VendorLeadRequested $event): void
    {
        $lead = $event->lead;
        $vendor = User::find($lead->vendor_id);

        if ($vendor === null) {
            return;
        }

        $search = $lead->buyer_search ?? LeadBuyerContextResolver::DEFAULT_SEARCH;
        $area = $lead->buyer_area ?? LeadBuyerContextResolver::DEFAULT_AREA;
        $cost = $this->pricing->costFor($vendor);
        $balance = $this->wallet->balance($vendor);
        $hours = max(1, (int) config('billing.leads.accept_window_hours', 3));

        $notification = $this->recordInApp($lead->id, $vendor, $search, $area, $cost, $balance, $hours);

        $to = WhatsAppPhone::toE164((string) ($vendor->whatsapp_number ?? ''));

        if ($to === null) {
            $this->emailFallback->queueFallbackEmail($notification);

            return;
        }

        // Thrown failures retry via ShouldQueue; the in-app record already exists.
        $this->whatsApp->sendTemplate(
            $to,
            (string) config('services.whatsapp.templates.lead_request'),
            (string) config('services.whatsapp.template_language', 'en'),
            [
                [
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $text) => ['type' => 'text', 'text' => $text],
                        [
                            $vendor->business_name ?? $vendor->name,
                            $search,
                            $area,
                            (string) $cost,
                            (string) $balance,
                            (string) $hours,
                        ],
                    ),
                ],
                $this->quickReply(0, LeadAcceptanceService::ACCEPT_PAYLOAD.':'.$lead->id),
                $this->quickReply(1, LeadAcceptanceService::DECLINE_PAYLOAD.':'.$lead->id),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function quickReply(int $index, string $payload): array
    {
        return [
            'type' => 'button',
            'sub_type' => 'quick_reply',
            'index' => (string) $index,
            'parameters' => [['type' => 'payload', 'payload' => $payload]],
        ];
    }

    private function recordInApp(int $leadId, User $vendor, string $search, string $area, int $cost, int $balance, int $hours): Notification
    {
        $existing = Notification::where('user_id', $vendor->id)
            ->where('type', self::TYPE)
            ->where('data->lead_id', $leadId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->notifications->create([
            'user_id' => $vendor->id,
            'type' => self::TYPE,
            'message' => sprintf(
                'New lead request: a buyer near %s is looking for %s. Accept within %d hours for %d coins (balance %d) to get their contact.',
                $area,
                $search,
                $hours,
                $cost,
                $balance,
            ),
            'data' => [
                'action' => 'lead_request',
                'url' => route('vendor.leads', ['state' => 'pending']),
                'lead_id' => $leadId,
                'cost' => $cost,
                'balance' => $balance,
            ],
        ]);
    }
}
