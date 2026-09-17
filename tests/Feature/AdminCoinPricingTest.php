<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Billing\Events\CategoryLeadCostChanged;
use ModulesShoppingComplex\Billing\Listeners\AnnounceLeadCostChange;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Jobs\SendVendorReminder;
use Tests\TestCase;

class AdminCoinPricingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    private function category(int $cost = 5): Category
    {
        return Category::create(['name' => 'Electronics', 'slug' => 'electronics-'.uniqid(), 'lead_coin_cost' => $cost]);
    }

    private function vendorIn(?Category $category, ?int $override = null): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'whatsapp_number' => '08031234567',
            'category_id' => $category?->id,
            'lead_coin_cost_override' => $override,
        ]);
    }

    // ==================== Access ====================

    public function test_a_non_admin_cannot_view_lead_pricing(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'email_verified_at' => now()]);

        $this->actingAs($vendor)->get('/admin/coin-pricing')->assertForbidden();
    }

    public function test_the_dashboard_lists_categories_with_their_cost(): void
    {
        $category = $this->category(10);
        $this->vendorIn($category);
        $this->vendorIn($category, override: 3);

        $this->actingAs($this->admin)->get('/admin/coin-pricing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/CoinPricing', false)
                ->where('defaultCost', 5)
                ->where('categories.0.lead_coin_cost', 10)
                ->where('categories.0.vendors_on_tier', 1)
                ->where('categories.0.vendors_with_override', 1));
    }

    // ==================== Updating a rate ====================

    public function test_an_admin_can_change_a_category_rate(): void
    {
        Queue::fake();
        $category = $this->category(5);

        $this->actingAs($this->admin)
            ->patch("/admin/coin-pricing/categories/{$category->id}", ['lead_coin_cost' => 12])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(12, $category->fresh()->lead_coin_cost);
    }

    public function test_changing_a_rate_only_notifies_affected_vendors(): void
    {
        Queue::fake();

        $category = $this->category(5);
        $other = $this->category(5);

        $affected = $this->vendorIn($category);
        $onOverride = $this->vendorIn($category, override: 2);
        $elsewhere = $this->vendorIn($other);

        // Run the listener directly to assert the fan-out target set.
        (new AnnounceLeadCostChange)->handle(new CategoryLeadCostChanged($category, 5, 12));

        Queue::assertPushed(SendVendorReminder::class, 1);
        Queue::assertPushed(
            SendVendorReminder::class,
            fn (SendVendorReminder $job) => $job->vendor->id === $affected->id,
        );
        Queue::assertNotPushed(
            SendVendorReminder::class,
            fn (SendVendorReminder $job) => in_array($job->vendor->id, [$onOverride->id, $elsewhere->id], true),
        );
    }

    public function test_the_update_queues_the_announcement_listener(): void
    {
        Queue::fake();
        $category = $this->category(5);

        $this->actingAs($this->admin)
            ->patch("/admin/coin-pricing/categories/{$category->id}", ['lead_coin_cost' => 20]);

        Queue::assertPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $job) => $job->class === AnnounceLeadCostChange::class,
        );
    }

    public function test_an_unchanged_rate_notifies_no_one(): void
    {
        Queue::fake();
        $category = $this->category(8);
        $this->vendorIn($category);

        $this->actingAs($this->admin)
            ->patch("/admin/coin-pricing/categories/{$category->id}", ['lead_coin_cost' => 8])
            ->assertSessionHas('info');

        Queue::assertNothingPushed();
    }

    public function test_a_rate_below_one_is_rejected(): void
    {
        Queue::fake();
        $category = $this->category(5);

        $this->actingAs($this->admin)
            ->patchJson("/admin/coin-pricing/categories/{$category->id}", ['lead_coin_cost' => 0])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lead_coin_cost');

        $this->assertSame(5, $category->fresh()->lead_coin_cost);
        Queue::assertNothingPushed();
    }
}
