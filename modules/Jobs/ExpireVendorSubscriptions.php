<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Events\SystemAlertEvent;
use ModulesShoppingComplex\Repositories\SubscriptionRepository;
use ModulesShoppingComplex\Services\NotificationService;

class ExpireVendorSubscriptions implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Hold the unique lock for up to 1 hour to prevent overlapping runs.
     */
    public int $uniqueFor = 3600;

    /**
     * Expire all overdue active subscriptions and notify the affected vendors.
     *
     * Free plans (expires_at = 2099-12-31) have expires_at > now(), so they are
     * never returned by the query and are never touched.
     */
    public function handle(
        SubscriptionRepository $subscriptionRepository,
        NotificationService $notificationService,
    ): void {
        $subscriptions = $subscriptionRepository->getOverdueActiveSubscriptions()->collect();

        if ($subscriptions->isEmpty()) {
            return;
        }

        /** @var int[] $ids */
        $ids = $subscriptions->pluck('id')->all();

        $subscriptionRepository->batchExpire($ids);

        foreach ($subscriptions as $subscription) {
            $notificationService->send(new SystemAlertEvent(
                recipient: $subscription->vendor,
                message: 'Your subscription has expired. Renew your plan to continue enjoying premium features.',
                alertLevel: 'warning',
                data: ['action' => 'renew_subscription'],
            ));
        }
    }
}
