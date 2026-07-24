<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\NotificationEmailFallback;
use ModulesShoppingComplex\Repositories\UserOnlineStatusRepository;

class SendNotificationEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 600; // 10 minutes

    public function __construct(
        public Notification $notification
    ) {}

    public function uniqueId(): string
    {
        return 'notification-email-'.$this->notification->id;
    }

    public function handle(UserOnlineStatusRepository $onlineStatusRepository): void
    {
        $this->notification->refresh();

        if ($this->notification->read_at !== null) {
            return;
        }

        $status = $onlineStatusRepository->get($this->notification->user_id);

        if ($status?->is_online) {
            return;
        }

        $user = $this->notification->user;

        if (! $user) {
            return;
        }

        $user->notify(new NotificationEmailFallback($this->notification));
    }
}
