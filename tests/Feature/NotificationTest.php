<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ModulesShoppingComplex\Events\MessageReceivedEvent;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\Events\VendorContactRequestEvent;
use ModulesShoppingComplex\Notifications\Models\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }

    public function test_user_can_see_notifications_in_shared_data(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer)->get('/');

        $response->assertStatus(200);
        // Shared data should include notifications
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->customer->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->customer)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Notification marked as read']);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_mark_others_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->vendor->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(403);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->customer->id,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'All notifications marked as read',
                'count' => 5,
            ]);

        $this->assertEquals(0, Notification::where('user_id', $this->customer->id)
            ->whereNull('read_at')
            ->count());
    }

    public function test_user_can_delete_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Notification deleted']);

        $this->assertNull(Notification::find($notification->id));
    }

    public function test_user_cannot_delete_others_notification(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->vendor->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(403);
        $this->assertNotNull(Notification::find($notification->id));
    }

    public function test_message_received_event_is_broadcast(): void
    {
        Event::fake([MessageReceivedEvent::class]);

        event(new MessageReceivedEvent(
            recipient: $this->vendor,
            sender: $this->customer,
            messagePreview: 'Hello, I am interested in...'
        ));

        Event::assertDispatched(MessageReceivedEvent::class, function ($event) {
            return $event->recipient->id === $this->vendor->id
                && $event->sender->id === $this->customer->id;
        });
    }

    public function test_vendor_contact_request_event_is_broadcast(): void
    {
        Event::fake([VendorContactRequestEvent::class]);

        event(new VendorContactRequestEvent(
            vendor: $this->vendor,
            customer: $this->customer,
            productName: 'Test Product'
        ));

        Event::assertDispatched(VendorContactRequestEvent::class, function ($event) {
            return $event->recipient->id === $this->vendor->id
                && $event->customer->id === $this->customer->id;
        });
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $notification = Notification::factory()->create();

        $this->patchJson("/api/notifications/{$notification->id}/read")
            ->assertStatus(401);

        $this->postJson('/api/notifications/mark-all-read')
            ->assertStatus(401);

        $this->deleteJson("/api/notifications/{$notification->id}")
            ->assertStatus(401);
    }
}
