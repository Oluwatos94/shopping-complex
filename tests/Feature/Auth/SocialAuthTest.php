<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_user_can_redirect_to_google_oauth(): void
    {
        $response = $this->get('/auth/google');

        $response->assertRedirect();
    }

    public function test_new_user_can_login_with_google(): void
    {
        // Mock the Socialite facade
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google_123');
        $socialiteUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('New User');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        // Call the callback route
        $response = $this->get('/auth/google/callback');

        // Assert user was created and redirected
        $response->assertRedirect('/');
        $response->assertSessionHas('success', 'Successfully logged in with Google!');

        // Assert user exists in database
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'google_id' => 'google_123',
            'role' => 'customer',
        ]);

        // Assert user is authenticated
        $this->assertAuthenticated();

        // Assert email is verified
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_user_can_login_with_google(): void
    {
        // Create an existing user with Google ID
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => 'google_456',
        ]);

        // Mock the Socialite facade
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google_456');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        // Call the callback route
        $response = $this->get('/auth/google/callback');

        // Assert redirected and authenticated
        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertEquals($user->id, auth()->id());
    }

    public function test_google_account_links_to_existing_email(): void
    {
        // Create a user without Google ID
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'google_id' => null,
        ]);

        // Mock the Socialite facade
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google_789');
        $socialiteUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Existing User');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        // Call the callback route
        $response = $this->get('/auth/google/callback');

        // Assert user was updated with Google ID
        $user->refresh();
        $this->assertEquals('google_789', $user->google_id);
        $this->assertNotNull($user->email_verified_at);

        // Assert redirected and authenticated
        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertEquals($user->id, auth()->id());
    }

    public function test_failed_google_authentication_redirects_to_login(): void
    {
        // Mock Socialite to throw an exception
        Socialite::shouldReceive('driver->user')->andThrow(new \Exception('OAuth failed'));

        // Call the callback route
        $response = $this->get('/auth/google/callback');

        // Assert redirected to login with error message
        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Failed to authenticate with Google. Please try again.');

        // Assert user is not authenticated
        $this->assertGuest();
    }

    public function test_google_login_creates_customer_by_default(): void
    {
        // Mock the Socialite facade
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn('google_999');
        $socialiteUser->shouldReceive('getEmail')->andReturn('customer@example.com');
        $socialiteUser->shouldReceive('getName')->andReturn('Customer User');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

        Socialite::shouldReceive('driver->user')->andReturn($socialiteUser);

        // Call the callback route
        $response = $this->get('/auth/google/callback');

        // Assert user was created with customer role
        $this->assertDatabaseHas('users', [
            'email' => 'customer@example.com',
            'role' => 'customer',
        ]);
    }
}
