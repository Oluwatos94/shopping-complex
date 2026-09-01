<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\AdminInvitationNotification;
use ReflectionProperty;
use Tests\TestCase;

class AdminInviteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    public function test_admin_can_invite_a_new_administrator(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post('/admin/users/invite', [
                'name' => 'Robert Smith',
                'email' => 'robert@jiidaa.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $invitee = User::where('email', 'robert@jiidaa.com')->first();

        $this->assertNotNull($invitee);
        $this->assertSame('admin', $invitee->role);
        $this->assertNotNull($invitee->email_verified_at);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'robert@jiidaa.com']);

        Notification::assertSentTo($invitee, AdminInvitationNotification::class);
    }

    public function test_invitee_can_set_their_password_and_log_in(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)->post('/admin/users/invite', [
            'name' => 'Robert Smith',
            'email' => 'robert@jiidaa.com',
        ]);

        $token = $this->emailedToken('robert@jiidaa.com');

        // The invitee opens the link in their own browser, not the inviter's session.
        auth()->logout();

        $this->post('/password/reset', [
            'token' => $token,
            'email' => 'robert@jiidaa.com',
            'password' => 'Str0ng!Passw0rd#2026',
            'password_confirmation' => 'Str0ng!Passw0rd#2026',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'robert@jiidaa.com']);
        $this->assertTrue(auth()->attempt([
            'email' => 'robert@jiidaa.com',
            'password' => 'Str0ng!Passw0rd#2026',
        ]));
    }

    public function test_invite_is_rejected_for_an_existing_email(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/users/invite', [
                'name' => 'Duplicate',
                'email' => $this->admin->email,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_non_admins_cannot_invite(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'email_verified_at' => now()]);

        $this->actingAs($vendor)
            ->post('/admin/users/invite', ['name' => 'Robert', 'email' => 'robert@jiidaa.com'])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'robert@jiidaa.com']);
    }

    /**
     * The plaintext token is only ever held by the notification — only its hash
     * is stored — so read it back off the notification that was sent.
     */
    private function emailedToken(string $email): string
    {
        $user = User::where('email', $email)->firstOrFail();
        $token = null;

        Notification::assertSentTo(
            $user,
            AdminInvitationNotification::class,
            function (AdminInvitationNotification $notification) use (&$token) {
                $property = new ReflectionProperty($notification, 'token');
                $token = (string) $property->getValue($notification);

                return true;
            }
        );

        $this->assertNotNull($token);
        $this->assertTrue(Hash::check(
            $token,
            (string) DB::table('password_reset_tokens')->where('email', $email)->value('token')
        ));

        return $token;
    }
}
