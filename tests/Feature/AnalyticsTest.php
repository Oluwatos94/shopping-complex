<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\ProductView;
use ModulesShoppingComplex\Models\ProfileView;
use ModulesShoppingComplex\Models\User;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $vendor;

    protected User $customer;

    protected User $otherVendor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->otherVendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);
    }

    // ==================== Authentication & Authorization ====================

    public function test_unauthenticated_user_cannot_access_analytics(): void
    {
        $response = $this->getJson('/vendor/analytics');

        $response->assertStatus(401);
    }

    public function test_customer_cannot_access_analytics(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/vendor/analytics');

        $response->assertStatus(403);
    }

    public function test_vendor_can_access_analytics(): void
    {
        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics');

        $response->assertStatus(200);
    }

    // ==================== Overview ====================

    public function test_overview_returns_correct_structure(): void
    {
        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'overview' => [
                    'chat_contacts',
                    'profile_views',
                    'product_views',
                    'average_view_value',
                    'followers_count',
                    'active_products',
                    'period' => ['start_date', 'end_date'],
                ],
                'chatContacts' => [
                    'total',
                    'daily',
                    'period',
                ],
                'profileViews' => [
                    'total',
                    'daily',
                    'period',
                ],
                'topProducts' => [
                    'products',
                    'average_view_value',
                    'period',
                ],
            ]);
    }

    public function test_new_vendor_gets_zero_counts(): void
    {
        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics');

        $response->assertStatus(200)
            ->assertJsonPath('overview.chat_contacts', 0)
            ->assertJsonPath('overview.profile_views', 0)
            ->assertJsonPath('overview.product_views', 0)
            ->assertJsonPath('overview.average_view_value', 0)
            ->assertJsonPath('overview.followers_count', 0)
            ->assertJsonPath('overview.active_products', 0);
    }

    public function test_overview_counts_chat_contacts(): void
    {
        // Create 3 conversations for this vendor
        Conversation::factory()->count(3)
            ->forVendor($this->vendor)
            ->forCustomer($this->customer)
            ->create();

        // Create 2 conversations for other vendor (should not count)
        Conversation::factory()->count(2)
            ->forVendor($this->otherVendor)
            ->forCustomer($this->customer)
            ->create();

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200)
            ->assertJsonPath('overview.chat_contacts', 3);
    }

    public function test_overview_counts_profile_views(): void
    {
        ProfileView::factory()->count(5)
            ->forVendor($this->vendor)
            ->create();

        // Other vendor's views should not count
        ProfileView::factory()->count(2)
            ->forVendor($this->otherVendor)
            ->create();

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200)
            ->assertJsonPath('overview.profile_views', 5);
    }

    public function test_overview_counts_product_views(): void
    {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);

        ProductView::factory()->count(8)
            ->forProduct($product)
            ->create();

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200)
            ->assertJsonPath('overview.product_views', 8);
    }

    public function test_overview_counts_active_products(): void
    {
        Product::factory()->count(3)->create([
            'vendor_id' => $this->vendor->id,
            'is_active' => true,
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200)
            ->assertJsonPath('overview.active_products', 3);
    }

    // ==================== Date Range Filtering ====================

    public function test_date_range_filtering_excludes_out_of_range_data(): void
    {
        // Profile view within range
        ProfileView::factory()
            ->forVendor($this->vendor)
            ->create(['created_at' => now()->subDays(3)]);

        // Profile view outside range (2 months ago)
        ProfileView::factory()
            ->forVendor($this->vendor)
            ->create(['created_at' => now()->subMonths(2)]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?start_date='.now()->subWeek()->toDateString().'&end_date='.now()->toDateString());

        $response->assertStatus(200)
            ->assertJsonPath('overview.profile_views', 1);
    }

    public function test_period_daily_returns_only_today(): void
    {
        // View today
        ProfileView::factory()
            ->forVendor($this->vendor)
            ->create(['created_at' => now()]);

        // View yesterday
        ProfileView::factory()
            ->forVendor($this->vendor)
            ->create(['created_at' => now()->subDay()]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=daily');

        $response->assertStatus(200)
            ->assertJsonPath('overview.profile_views', 1);
    }

    public function test_period_weekly_returns_last_seven_days(): void
    {
        // 3 views within last week
        ProfileView::factory()->count(3)
            ->forVendor($this->vendor)
            ->create(['created_at' => now()->subDays(3)]);

        // View 2 weeks ago (outside range)
        ProfileView::factory()
            ->forVendor($this->vendor)
            ->create(['created_at' => now()->subWeeks(2)]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=weekly');

        $response->assertStatus(200)
            ->assertJsonPath('overview.profile_views', 3);
    }

    // ==================== Chat Contacts ====================

    public function test_chat_contacts_daily_breakdown(): void
    {
        $today = now()->startOfDay();

        Conversation::factory()
            ->forVendor($this->vendor)
            ->forCustomer($this->customer)
            ->create(['created_at' => $today]);

        $otherCustomer = User::factory()->create(['role' => 'customer']);

        Conversation::factory()
            ->forVendor($this->vendor)
            ->forCustomer($otherCustomer)
            ->create(['created_at' => $today->copy()->subDay()]);

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=weekly');

        $response->assertStatus(200)
            ->assertJsonPath('chatContacts.total', 2);

        $daily = $response->json('chatContacts.daily');
        $this->assertCount(2, $daily);
    }

    // ==================== Top Products ====================

    public function test_top_products_ordered_by_views(): void
    {
        $productA = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Product A',
            'price' => 50.00,
        ]);
        $productB = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Product B',
            'price' => 100.00,
        ]);

        // Product A gets 2 views
        ProductView::factory()->count(2)->forProduct($productA)->create();

        // Product B gets 5 views
        ProductView::factory()->count(5)->forProduct($productB)->create();

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200);

        $products = $response->json('topProducts.products');
        $this->assertCount(2, $products);
        $this->assertEquals('Product B', $products[0]['name']);
        $this->assertEquals(5, $products[0]['views_count']);
        $this->assertEquals('Product A', $products[1]['name']);
        $this->assertEquals(2, $products[1]['views_count']);
    }

    public function test_top_products_excludes_soft_deleted(): void
    {
        $activeProduct = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);
        $deletedProduct = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);
        $deletedProduct->delete(); // soft delete

        ProductView::factory()->count(3)->forProduct($activeProduct)->create();
        ProductView::factory()->count(5)->forProduct($deletedProduct)->create();

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200);

        $products = $response->json('topProducts.products');
        $this->assertCount(1, $products);
        $this->assertEquals($activeProduct->id, $products[0]['product_id']);
    }

    // ==================== Average View Value ====================

    public function test_average_view_value_calculated_correctly(): void
    {
        $product1 = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'price' => 100.00,
        ]);
        $product2 = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'price' => 200.00,
        ]);

        // Multiple views per product should not skew average
        ProductView::factory()->count(3)->forProduct($product1)->create();
        ProductView::factory()->count(1)->forProduct($product2)->create();

        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200);

        // Average of distinct products: (100 + 200) / 2 = 150
        $this->assertEquals(150.0, $response->json('overview.average_view_value'));
    }

    public function test_average_view_value_zero_when_no_views(): void
    {
        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?period=monthly');

        $response->assertStatus(200)
            ->assertJsonPath('overview.average_view_value', 0);
    }

    // ==================== View Recording ====================

    public function test_vendor_profile_visit_records_profile_view(): void
    {
        $this->actingAs($this->customer)
            ->get('/vendors/'.$this->vendor->slug);

        $this->assertDatabaseHas('profile_views', [
            'vendor_id' => $this->vendor->id,
            'viewer_id' => $this->customer->id,
        ]);
    }

    public function test_vendor_viewing_own_profile_does_not_record(): void
    {
        $this->actingAs($this->vendor)
            ->get('/vendors/'.$this->vendor->slug);

        $this->assertDatabaseMissing('profile_views', [
            'vendor_id' => $this->vendor->id,
            'viewer_id' => $this->vendor->id,
        ]);
    }

    public function test_product_visit_records_product_view(): void
    {
        $product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $this->actingAs($this->customer)
            ->get('/products/'.$product->slug);

        $this->assertDatabaseHas('product_views', [
            'product_id' => $product->id,
            'vendor_id' => $this->vendor->id,
            'viewer_id' => $this->customer->id,
        ]);
    }

    public function test_vendor_viewing_own_product_does_not_record(): void
    {
        $product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $this->actingAs($this->vendor)
            ->get('/products/'.$product->slug);

        $this->assertDatabaseMissing('product_views', [
            'product_id' => $product->id,
            'viewer_id' => $this->vendor->id,
        ]);
    }

    // ==================== View Deduplication ====================

    public function test_profile_view_deduplicates_same_viewer_same_day(): void
    {
        // Visit twice
        $this->actingAs($this->customer)
            ->get('/vendors/'.$this->vendor->slug);
        $this->actingAs($this->customer)
            ->get('/vendors/'.$this->vendor->slug);

        $count = ProfileView::where('vendor_id', $this->vendor->id)
            ->where('viewer_id', $this->customer->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_product_view_deduplicates_same_viewer_same_day(): void
    {
        $product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);

        $this->actingAs($this->customer)
            ->get('/products/'.$product->slug);
        $this->actingAs($this->customer)
            ->get('/products/'.$product->slug);

        $count = ProductView::where('product_id', $product->id)
            ->where('viewer_id', $this->customer->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    // ==================== Input Validation ====================

    public function test_invalid_date_range_falls_back_to_default(): void
    {
        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?start_date=invalid&end_date=also-invalid');

        $response->assertStatus(200);
        // Should not crash — falls back to default monthly range
        $response->assertJsonStructure(['overview' => ['period' => ['start_date', 'end_date']]]);
    }

    public function test_reversed_dates_fall_back_to_default(): void
    {
        $response = $this->actingAs($this->vendor)
            ->getJson('/vendor/analytics?start_date=2026-03-01&end_date=2026-01-01');

        $response->assertStatus(200);
        $response->assertJsonStructure(['overview' => ['period']]);
    }
}
