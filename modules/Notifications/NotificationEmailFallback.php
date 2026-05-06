<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use ModulesShoppingComplex\Models\Notification as NotificationModel;

class NotificationEmailFallback extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NotificationModel $notification
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->view('emails.notification-fallback', [
                'user' => $notifiable,
                'notification' => $this->notification,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'type' => $this->notification->type,
            'message' => $this->notification->message,
        ];
    }

    /**
     * Get the email subject based on notification type.
     * Uses config as single source of truth for notification type labels.
     */
    private function getSubject(): string
    {
        $label = config("notifications.types.{$this->notification->type}.label");

        if ($label) {
            return "New {$label} notification";
        }

        return 'New notification from jiidaa';
    }
}
