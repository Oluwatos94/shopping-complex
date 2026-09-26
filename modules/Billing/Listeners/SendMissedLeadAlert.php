<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Billing\Events\VendorLeadMissed;
use ModulesShoppingComplex\Identity\Models\User;
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
    ) {}

    public function handle(VendorLeadMissed $event): void
    {
        $lead = $event->lead;
        $vendor = User::find($lead->vendor_id);

        if ($vendor === null) {
            return;
        }

        $notification = DB::transaction(function () use ($vendor, $lead, $event) {
            User::whereKey($vendor->id)->lockForUpdate()->first();

            return $this->notifications->createOrUpdateGrouped(
                userId: $vendor->id,
                type: self::TYPE,
                message: sprintf(
                    'You received a new lead but did not have enough coins to bill it. Top up to stop missing paid leads — this one would have cost %d coins.',
                    $event->attemptedCost,
                ),
                data: [
                    'action' => 'top_up',
                    'url' => route('vendor.coins.packs'),
                    'lead_id' => $lead->id,
                    'missed_cost' => $event->attemptedCost,
                ],

                groupKey: self::TYPE.':'.$vendor->id,
            );
        });

        if ($notification->wasRecentlyCreated) {
            $this->emailFallback->queueFallbackEmail($notification);
        }
    }
}
