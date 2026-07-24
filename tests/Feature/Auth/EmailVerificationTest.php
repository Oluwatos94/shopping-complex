<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\VerifyEmailNotification;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1('wrong-email'),
            ]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_sent_on_registration(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'customer',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $response->assertRedirect('/email/verify');
    }

    public function test_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->post('/email/verification-notification');

        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $response->assertSessionHas('status', 'Verification link sent!');
    }

    public function test_verified_user_cannot_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(); // Already verified

        $response = $this->actingAs($user)
            ->post('/email/verification-notification');

        Notification::assertNothingSent();
        $response->assertRedirect('/');
    }

    public function test_already_verified_email_returns_appropriate_message(): void
    {
        $user = User::factory()->create(); // Already verified

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/');
        $response->assertSessionHas('status', 'Email already verified!');
    }

    public function test_verification_link_expires_after_60_minutes(): void
    {
        $user = User::factory()->unverified()->create();

        // Create an expired verification URL (61 minutes ago)
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(61),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // Signed URL middleware will reject expired URLs
        $response->assertStatus(403);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_guest_cannot_access_verification_notice(): void
    {
        $response = $this->get('/email/verify');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_verify_email(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/login');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_event_is_dispatched(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }
}
