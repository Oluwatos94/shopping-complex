<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Notifications\NotificationEmailFallback;
use ModulesShoppingComplex\Repositories\UserOnlineStatusRepository;

class SendNotificationEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 600; // 10 minutes

    public function __construct(
        public Notification $notification
    ) {}

    /**
     * Get the unique ID for the job.
     * Prevents duplicate email jobs for the same notification.
     */
    public function uniqueId(): string
    {
        return 'notification-email-'.$this->notification->id;
    }

    /**
     * Execute the job.
     */
    public function handle(UserOnlineStatusRepository $onlineStatusRepository): void
    {
        // Check if notification is still unread
        $this->notification->refresh();

        if ($this->notification->read_at !== null) {
            return; // Already read, no need to send email
        }

        // Check if user came online since job was queued
        $status = $onlineStatusRepository->get($this->notification->user_id);

        if ($status?->is_online) {
            return; // User is online, they'll see it in-app
        }

        // Load the user relationship
        $user = $this->notification->user;

        if (! $user) {
            return;
        }

        // Send the email fallback notification
        $user->notify(new NotificationEmailFallback($this->notification));
    }
}
