<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use ModulesShoppingComplex\Jobs\SendNotificationEmailJob;
use ModulesShoppingComplex\Models\Notification;

final readonly class NotificationEmailService
{
    /**
     * Queue a fallback email for a notification
     *
     * The email is delayed to give the user time to see the notification in-app.
     * If the user reads the notification before the delay, the email won't be sent.
     */
    public function queueFallbackEmail(Notification $notification): void
    {
        $delayMinutes = (int) config('notifications.email_fallback_delay_minutes', 5);

        SendNotificationEmailJob::dispatch($notification)
            ->delay(now()->addMinutes($delayMinutes));
    }
}
