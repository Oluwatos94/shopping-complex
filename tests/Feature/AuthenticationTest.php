<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Models\User;
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

        $response->assertRedirect('/');
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

    public function test_vendor_registration_requires_business_info(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Vendor',
            'email' => 'vendor@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'vendor',
            // Missing bio and business_name
        ]);

        $response->assertSessionHasErrors(['bio', 'business_name']);
    }

    public function test_vendor_can_register_with_business_info(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test Vendor',
            'email' => 'vendor@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'vendor',
            'bio' => 'I am a vendor selling great products',
            'business_name' => 'My Great Business',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = User::where('email', 'vendor@example.com')->first();
        $this->assertEquals('vendor', $user->role);
        $this->assertEquals('My Great Business', $user->business_name);
    }
}
