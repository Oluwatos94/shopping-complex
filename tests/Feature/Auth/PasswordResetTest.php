<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\ResetPasswordNotification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/password/reset');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/password/reset/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/password/reset', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

            $response->assertSessionHasNoErrors();
            $response->assertRedirect('/login');

            // Verify password was changed
            $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));

            return true;
        });
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/password/reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_fails_with_invalid_email(): void
    {
        $response = $this->post('/password/reset', [
            'token' => 'some-token',
            'email' => 'nonexistent@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_reset_requires_strong_password(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            // Test weak password (no symbols)
            $response = $this->post('/password/reset', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
            ]);

            $response->assertSessionHasErrors('password');

            return true;
        });
    }

    public function test_password_reset_request_is_rate_limited(): void
    {
        $user = User::factory()->create();

        // Make 3 successful requests to hit the limit
        for ($i = 0; $i < 3; $i++) {
            $this->post('/password/email', ['email' => $user->email]);
        }

        // The 4th request should be rate limited
        $response = $this->post('/password/email', ['email' => $user->email]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(str_contains($response->getSession()->get('errors')->first('email'), 'Too many'));
    }

    public function test_password_reset_token_expires_after_one_hour(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            // Manually update the token creation time to 61 minutes ago
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->update(['created_at' => now()->subMinutes(61)]);

            $response = $this->post('/password/reset', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

            $response->assertSessionHasErrors('email');

            return true;
        });
    }

    public function test_old_password_reset_tokens_are_deleted_after_use(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $this->post('/password/reset', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

            // Verify token was deleted
            $this->assertDatabaseMissing('password_reset_tokens', [
                'email' => $user->email,
            ]);

            return true;
        });
    }

    public function test_password_reset_validates_password_confirmation(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/password/email', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/password/reset', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]);

            $response->assertSessionHasErrors('password');

            return true;
        });
    }
}
