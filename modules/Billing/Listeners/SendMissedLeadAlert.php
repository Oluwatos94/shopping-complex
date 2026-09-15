<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\VendorLeadMissed;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\LeadPricingService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;
use ModulesShoppingComplex\Notifications\Services\NotificationEmailService;

class SendMissedLeadAlert implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $backoff = 30;

    private const TYPE = 'lead_missed';

    public function __construct(
        private readonly NotificationRepository $notifications,
        private readonly NotificationEmailService $emailFallback,
        private readonly LeadPricingService $pricing,
    ) {}

    public function handle(VendorLeadMissed $event): void
    {
        $lead = $event->lead;
        $vendor = User::find($lead->vendor_id);

        if ($vendor === null) {
            return;
        }

        $notification = $this->recordInApp($lead, $vendor);

        $this->emailFallback->queueFallbackEmail($notification);
    }

    private function recordInApp(BillableLead $lead, User $vendor): Notification
    {
        $existing = Notification::where('user_id', $vendor->id)
            ->where('type', self::TYPE)
            ->where('data->lead_id', $lead->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $missedCost = $this->pricing->costFor($vendor);

        return $this->notifications->create([
            'user_id' => $vendor->id,
            'type' => self::TYPE,
            'message' => sprintf(
                'You received a new lead but had no coins to claim it. Top up to stop missing paid leads — this one would have cost %d coins.',
                $missedCost,
            ),
            'data' => [
                'action' => 'top_up',
                'url' => route('vendor.coins.packs'),
                'lead_id' => $lead->id,
                'missed_cost' => $missedCost,
            ],
        ]);
    }
}
