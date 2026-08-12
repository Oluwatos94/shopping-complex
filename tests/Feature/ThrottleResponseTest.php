<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThrottleResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_throttled_page_load_renders_an_error_page_instead_of_redirecting(): void
    {
        // The guest limiter allows 120/min; the next one is throttled.
        for ($i = 0; $i < 120; $i++) {
            $this->get('/login');
        }

        $response = $this->get('/login');

        // A redirect here would send the browser back to the throttled page,
        // which throttles again — an unbreakable loop until the window rolls.
        $response->assertStatus(429);
        $response->assertSee('Slow down');
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_throttled_page_load_still_answers_json_clients_with_json(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->get('/login');
        }

        $this->getJson('/login')
            ->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_throttled_form_post_redirects_back_with_the_message(): void
    {
        $attempt = fn () => $this->from('/register')->post('/register', [
            'name' => 'Rate Limited',
            'email' => 'throttled@gmail.com',
            'password' => 'too-weak',
            'password_confirmation' => 'mismatch',
            'role' => 'customer',
        ]);

        // The register limiter allows 5/min per email; validation fails each
        // time, so the account is never created and the session stays guest.
        for ($i = 0; $i < 5; $i++) {
            $attempt();
        }

        $attempt()
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'email' => 'Too many registration attempts. Please wait a moment and try again.',
            ]);
    }
}
