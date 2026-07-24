<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'customer',
        ]);

        $response->assertRedirect('/email/verify');
        $response->assertSessionHas('status');
        $this->assertAuthenticated();
    }

    public function test_users_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_users_cannot_login_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
