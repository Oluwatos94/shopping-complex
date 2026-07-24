<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Models\UserOnlineStatus;
use ModulesShoppingComplex\Notifications\Jobs\SendNotificationEmailJob;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\NotificationEmailFallback;
use ModulesShoppingComplex\Repositories\UserOnlineStatusRepository;
use Tests\TestCase;

class NotificationEmailFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    public function test_email_job_can_be_dispatched(): void
    {
        Queue::fake();

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
        ]);

        SendNotificationEmailJob::dispatch($notification)->delay(now()->addMinutes(5));

        Queue::assertPushed(SendNotificationEmailJob::class);
    }

    public function test_email_not_sent_if_notification_already_read(): void
    {
        NotificationFacade::fake();

        $notification = Notification::factory()->read()->create([
            'user_id' => $this->user->id,
        ]);

        UserOnlineStatus::create([
            'user_id' => $this->user->id,
            'is_online' => false,
            'last_seen_at' => now()->subMinutes(10),
        ]);

        $job = new SendNotificationEmailJob($notification);
        $job->handle(app(UserOnlineStatusRepository::class));

        NotificationFacade::assertNothingSent();
    }

    public function test_email_not_sent_if_user_is_online(): void
    {
        NotificationFacade::fake();

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        UserOnlineStatus::create([
            'user_id' => $this->user->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $job = new SendNotificationEmailJob($notification);
        $job->handle(app(UserOnlineStatusRepository::class));

        NotificationFacade::assertNothingSent();
    }

    public function test_email_sent_when_user_offline_and_notification_unread(): void
    {
        NotificationFacade::fake();

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        UserOnlineStatus::create([
            'user_id' => $this->user->id,
            'is_online' => false,
            'last_seen_at' => now()->subMinutes(10),
        ]);

        $job = new SendNotificationEmailJob($notification);
        $job->handle(app(UserOnlineStatusRepository::class));

        NotificationFacade::assertSentTo(
            $this->user,
            NotificationEmailFallback::class,
            function ($notification, $channels) {
                return in_array('mail', $channels);
            }
        );
    }

    public function test_email_sent_when_no_online_status_record_exists(): void
    {
        NotificationFacade::fake();

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        // No UserOnlineStatus record - user never connected
        $job = new SendNotificationEmailJob($notification);
        $job->handle(app(UserOnlineStatusRepository::class));

        NotificationFacade::assertSentTo(
            $this->user,
            NotificationEmailFallback::class
        );
    }
}
