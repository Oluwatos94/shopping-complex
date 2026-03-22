<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\NotificationRepository;
use Tests\TestCase;

class NotificationGroupingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected NotificationRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->repository = app(NotificationRepository::class);
    }

    public function test_notifications_are_grouped_by_key(): void
    {
        // First notification
        $notification1 = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'John sent you a message',
            data: ['sender_id' => 1],
            groupKey: 'message_from_1'
        );

        $this->assertEquals(1, $notification1->group_count);
        $this->assertFalse($notification1->is_grouped);

        // Second notification with same group key
        $notification2 = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'John sent another message',
            data: ['sender_id' => 1],
            groupKey: 'message_from_1'
        );

        // Should update existing notification
        $this->assertEquals($notification1->id, $notification2->id);
        $this->assertEquals(2, $notification2->group_count);
        $this->assertTrue($notification2->is_grouped);
    }

    public function test_different_group_keys_create_separate_notifications(): void
    {
        $notification1 = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'John sent you a message',
            data: ['sender_id' => 1],
            groupKey: 'message_from_1'
        );

        $notification2 = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'Jane sent you a message',
            data: ['sender_id' => 2],
            groupKey: 'message_from_2'
        );

        $this->assertNotEquals($notification1->id, $notification2->id);
        $this->assertEquals(1, $notification1->group_count);
        $this->assertEquals(1, $notification2->group_count);
    }

    public function test_read_notifications_are_not_grouped(): void
    {
        // Create and mark as read
        $notification1 = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'First message',
            data: [],
            groupKey: 'test_group'
        );
        $this->repository->markAsRead($notification1);

        // New notification should create new record
        $notification2 = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'Second message',
            data: [],
            groupKey: 'test_group'
        );

        $this->assertNotEquals($notification1->id, $notification2->id);
        $this->assertEquals(1, $notification2->group_count);
    }

    public function test_notifications_outside_grouping_window_create_new_records(): void
    {
        // Create old notification and manually set created_at to outside the window
        $notification1 = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'message_received',
            'message' => 'Old message',
            'data' => [],
            'group_key' => 'test_group',
            'is_grouped' => false,
            'group_count' => 1,
        ]);

        // Update timestamp directly in database to simulate old notification
        Notification::where('id', $notification1->id)
            ->update(['created_at' => now()->subHours(25)]);

        // New notification should create new record
        $notification2 = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'New message',
            data: [],
            groupKey: 'test_group'
        );

        $this->assertNotEquals($notification1->id, $notification2->id);
    }

    public function test_grouping_preserves_message_and_data(): void
    {
        $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'First message',
            data: ['key' => 'first'],
            groupKey: 'test_group'
        );

        $notification = $this->repository->createOrUpdateGrouped(
            userId: $this->user->id,
            type: 'message_received',
            message: 'Updated message',
            data: ['key' => 'updated'],
            groupKey: 'test_group'
        );

        $this->assertEquals('Updated message', $notification->message);
        $this->assertEquals(['key' => 'updated'], $notification->data);
    }
}
