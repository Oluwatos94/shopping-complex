<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\Models\NotificationPreference;
use Tests\TestCase;

class NotificationPreferenceTest extends TestCase
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

    public function test_user_can_view_preferences_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/notifications/preferences');

        $response->assertStatus(200);
    }

    public function test_user_can_update_preference(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/notifications/preferences/message_received', [
                'email_enabled' => false,
                'push_enabled' => true,
                'in_app_enabled' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'message_received',
            'email_enabled' => false,
            'push_enabled' => true,
            'in_app_enabled' => true,
        ]);
    }

    public function test_preference_update_validates_type(): void
    {
        // Invalid notification types are rejected at the route level with a 404
        // This is more secure than allowing the request to reach the controller
        $response = $this->actingAs($this->user)
            ->post('/notifications/preferences/invalid_type', [
                'email_enabled' => false,
            ]);

        $response->assertStatus(404);
    }

    public function test_preference_validation_requires_boolean(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/notifications/preferences/message_received', [
                'email_enabled' => 'not_a_boolean',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email_enabled');
    }

    public function test_existing_preference_is_updated(): void
    {
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => 'message_received',
            'email_enabled' => true,
            'push_enabled' => true,
            'in_app_enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post('/notifications/preferences/message_received', [
                'email_enabled' => false,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'message_received',
            'email_enabled' => false,
        ]);

        // Should only have one record
        $this->assertEquals(1, NotificationPreference::where('user_id', $this->user->id)
            ->where('notification_type', 'message_received')
            ->count());
    }

    public function test_unauthenticated_user_cannot_access_preferences(): void
    {
        $this->get('/notifications/preferences')
            ->assertRedirect('/login');

        $this->post('/notifications/preferences/message_received', [
            'email_enabled' => false,
        ])->assertRedirect('/login');
    }
}
