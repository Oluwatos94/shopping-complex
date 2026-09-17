<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class VendorCoinCapTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vendor_sets_a_daily_cap(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)->post('/vendor/coin-cap', ['daily_coin_cap' => 40])->assertRedirect();

        $this->assertSame(40, $vendor->fresh()->daily_coin_cap);
    }

    public function test_a_vendor_removes_the_cap(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'daily_coin_cap' => 40]);

        $this->actingAs($vendor)->post('/vendor/coin-cap', ['daily_coin_cap' => null])->assertRedirect();

        $this->assertNull($vendor->fresh()->daily_coin_cap);
    }

    public function test_the_cap_is_bounded(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $this->actingAs($vendor)->post('/vendor/coin-cap', ['daily_coin_cap' => 0])->assertSessionHasErrors('daily_coin_cap');
        $this->actingAs($vendor)->post('/vendor/coin-cap', ['daily_coin_cap' => 200000])->assertSessionHasErrors('daily_coin_cap');

        $this->assertNull($vendor->fresh()->daily_coin_cap);
    }

    public function test_a_non_vendor_cannot_set_a_cap(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->post('/vendor/coin-cap', ['daily_coin_cap' => 40])->assertRedirect(route('home'));

        $this->assertNull($user->fresh()->daily_coin_cap);
    }
}
