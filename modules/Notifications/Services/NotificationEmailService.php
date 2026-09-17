<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Services;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Jobs\SendNotificationEmailJob;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Shared\Support\MarkdownRenderer;

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

    public function sendSupportReply(User $customer, string $subject, string $body): void
    {
        Mail::send('emails.customer-support', [
            'customer' => $customer,
            'bodyHtml' => MarkdownRenderer::toHtml($body),
        ], function (Message $mail) use ($customer, $subject): void {
            $mail->to($customer->email, $customer->name)->subject($subject);
        });
    }
}
