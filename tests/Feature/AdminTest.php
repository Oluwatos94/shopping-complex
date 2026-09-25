<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Analytics\Services\GrowthAnalyticsService;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Models\VendorOnboarding;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected User $vendor;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Approving a vendor assigns the free plan, so it must exist in the DB.
     */
    private function seedFreePlan(): void
    {
        SubscriptionPlan::firstOrCreate(['slug' => 'free'], [
            'name' => 'Free',
            'price' => 0.00,
            'product_limit' => 10,
            'search_priority' => 0,
            'features' => ['List up to 10 products'],
            'is_active' => true,
        ]);
    }

    // ==================== Authentication & Authorization ====================

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $this->getJson('/admin/dashboard')->assertStatus(401);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $this->actingAs($this->customer)
            ->getJson('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_vendor_cannot_access_admin_dashboard(): void
    {
        $this->actingAs($this->vendor)
            ->getJson('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/dashboard')
            ->assertStatus(200);
    }

    // ==================== Platform Stats ====================

    public function test_stats_returns_correct_structure(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/dashboard')
            ->assertStatus(200)
            ->assertJsonStructure([
                'users' => ['total', 'admins', 'vendors', 'customers'],
                'products' => ['total'],
                'vendors' => ['approved', 'pending_review', 'rejected', 'draft'],
            ]);
    }

    public function test_stats_user_counts_match_database(): void
    {
        $expectedAdmins = User::where('role', 'admin')->count();
        $expectedVendors = User::where('role', 'vendor')->count();
        $expectedCustomers = User::where('role', 'customer')->count();
        $expectedTotal = User::count();

        $response = $this->actingAs($this->admin)
            ->getJson('/admin/dashboard')
            ->assertStatus(200);

        $this->assertSame($expectedTotal, $response->json('users.total'));
        $this->assertSame($expectedAdmins, $response->json('users.admins'));
        $this->assertSame($expectedVendors, $response->json('users.vendors'));
        $this->assertSame($expectedCustomers, $response->json('users.customers'));
    }

    public function test_stats_product_total_matches_database(): void
    {
        Product::factory()->create(['vendor_id' => $this->vendor->id]);
        Product::factory()->create(['vendor_id' => $this->vendor->id]);

        $expected = Product::count();

        $response = $this->actingAs($this->admin)
            ->getJson('/admin/dashboard')
            ->assertStatus(200);

        $this->assertSame($expected, $response->json('products.total'));
    }

    // ==================== User Management ====================

    public function test_admin_can_list_users(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/users')
            ->assertStatus(200)
            ->assertJsonStructure([
                'users' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'role'],
                    ],
                    'total',
                    'per_page',
                    'current_page',
                ],
            ]);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/users?role=customer')
            ->assertStatus(200);

        foreach ($response->json('users.data') as $user) {
            $this->assertSame('customer', $user['role']);
        }
    }

    public function test_admin_can_search_users_by_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/users?search='.urlencode($this->customer->name))
            ->assertStatus(200);

        $this->assertNotEmpty($response->json('users.data'));
    }

    public function test_per_page_is_clamped_between_1_and_100(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/users?per_page=0')
            ->assertStatus(200)
            ->assertJsonPath('users.per_page', 1);

        $this->actingAs($this->admin)
            ->getJson('/admin/users?per_page=999')
            ->assertStatus(200)
            ->assertJsonPath('users.per_page', 100);
    }

    public function test_admin_can_update_user_role(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/admin/users/{$this->customer->id}", ['role' => 'vendor'])
            ->assertStatus(200)
            ->assertJsonPath('message', 'User updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $this->customer->id,
            'role' => 'vendor',
        ]);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/admin/users/{$this->admin->id}", ['role' => 'customer'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot change your own role.');

        // Role must remain unchanged
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_update_user_role_requires_valid_role(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/admin/users/{$this->customer->id}", ['role' => 'superuser'])
            ->assertStatus(422);
    }

    public function test_customer_cannot_update_user_role(): void
    {
        $this->actingAs($this->customer)
            ->patchJson("/admin/users/{$this->vendor->id}", ['role' => 'customer'])
            ->assertStatus(403);
    }

    // ==================== Vendor Approval ====================

    public function test_admin_can_list_pending_vendors(): void
    {
        VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        $this->actingAs($this->admin)
            ->getJson('/admin/vendors/pending')
            ->assertStatus(200)
            ->assertJsonCount(1, 'vendors.data')
            ->assertJsonStructure([
                'vendors' => [
                    'data' => [
                        '*' => ['id', 'user_id', 'status'],
                    ],
                    'total',
                    'per_page',
                    'current_page',
                ],
            ]);
    }

    public function test_admin_can_approve_vendor(): void
    {
        $this->seedFreePlan();

        $onboarding = VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/vendors/{$this->vendor->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('success', 'Vendor approved successfully.');

        $this->assertDatabaseHas('vendor_onboardings', [
            'id' => $onboarding->id,
            'status' => VendorOnboardingStatusEnum::APPROVED->value,
            'reviewed_by' => $this->admin->id,
        ]);
    }

    public function test_admin_can_reject_vendor(): void
    {
        $onboarding = VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        $this->actingAs($this->admin)
            ->post("/admin/vendors/{$this->vendor->id}/reject", [
                'rejection_reason' => 'Documents are incomplete.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Vendor rejected successfully.');

        $this->assertDatabaseHas('vendor_onboardings', [
            'id' => $onboarding->id,
            'status' => VendorOnboardingStatusEnum::REJECTED->value,
            'rejection_reason' => 'Documents are incomplete.',
            'reviewed_by' => $this->admin->id,
        ]);
    }

    public function test_reject_vendor_requires_rejection_reason(): void
    {
        VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/vendors/{$this->vendor->id}/reject", [])
            ->assertStatus(422);
    }

    public function test_approve_fails_if_no_pending_application(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/vendors/{$this->vendor->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('error', 'No pending application found for this vendor.');
    }

    public function test_cannot_approve_already_approved_vendor(): void
    {
        $this->seedFreePlan();

        VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        // First approval succeeds
        $this->actingAs($this->admin)
            ->post("/admin/vendors/{$this->vendor->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('success', 'Vendor approved successfully.');

        // Second approval (simulates race condition at application level) must fail
        $this->actingAs($this->admin)
            ->post("/admin/vendors/{$this->vendor->id}/approve")
            ->assertRedirect()
            ->assertSessionHas('error', 'No pending application found for this vendor.');
    }

    public function test_customer_cannot_approve_vendor(): void
    {
        VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        $this->actingAs($this->customer)
            ->postJson("/admin/vendors/{$this->vendor->id}/approve")
            ->assertStatus(403);
    }

    // ==================== Growth ====================

    public function test_growth_compares_the_week_so_far_with_the_same_days_last_week(): void
    {
        Carbon::setTestNow('2026-09-25 15:00:00');
        $before = $this->growthHeadline()['metrics']['new_vendors'];

        $this->vendorsJoinedOn('2026-09-15', 3);
        $this->vendorsJoinedOn('2026-09-19', 6);
        $this->vendorsJoinedOn('2026-09-22', 4);

        $after = $this->growthHeadline()['metrics']['new_vendors'];

        $this->assertSame(4, $after['value'] - $before['value']);
        $this->assertSame(3, $after['previous'] - $before['previous']);
        $this->assertSame(9, $after['last_complete'] - $before['last_complete']);
    }

    public function test_growth_headline_names_both_ranges(): void
    {
        Carbon::setTestNow('2026-09-25 15:00:00');

        $headline = $this->growthHeadline();

        $this->assertSame('21 Sep – 25 Sep', $headline['current_range']);
        $this->assertSame('14 Sep – 18 Sep', $headline['comparison_range']);
    }

    public function test_growth_counts_website_message_clicks_alongside_bot_contacts(): void
    {
        Carbon::setTestNow('2026-09-25 15:00:00');
        $before = $this->growthHeadline()['metrics'];

        $webVendor = User::factory()->create(['role' => 'vendor']);
        $botVendor = User::factory()->create(['role' => 'vendor']);

        $this->contactClick($webVendor, ViewSourceEnum::WEB);
        $this->contactClick($webVendor, ViewSourceEnum::WEB);
        $this->contactClick($botVendor, ViewSourceEnum::WHATSAPP);

        DB::table('whatsapp_interactions')->insert([
            'phone_number' => '2348012345678',
            'event_type' => WhatsAppInteractionEventEnum::CONTACT_REQUESTED->value,
            'vendor_id' => $botVendor->id,
            'created_at' => now(),
        ]);

        $after = $this->growthHeadline()['metrics'];

        // Two web clicks plus one bot request; the bot's own link click is not counted again.
        $this->assertSame(3, $after['contacts']['value'] - $before['contacts']['value']);
        $this->assertSame(2, $after['active_vendors']['value'] - $before['active_vendors']['value']);
    }

    public function test_growth_revenue_comes_from_completed_coin_purchases(): void
    {
        Carbon::setTestNow('2026-09-25 15:00:00');
        $before = $this->growthRevenue();

        $other = User::factory()->create(['role' => 'vendor']);

        $this->coinPurchase($this->vendor, 5000, '2026-09-10 12:00:00');
        $this->coinPurchase($this->vendor, 20000, '2026-08-20 12:00:00');
        $this->coinPurchase($other, 5000, '2026-09-20 12:00:00');
        $this->coinPurchase($other, 50000, null, CoinPurchaseStatusEnum::PENDING);

        $after = $this->growthRevenue();

        $this->assertSame(2, $after['paying_vendors'] - $before['paying_vendors']);
        $this->assertEqualsWithDelta(10000.0, $after['collected_this_month'] - $before['collected_this_month'], 0.001);
        $this->assertEqualsWithDelta(30000.0, $after['lifetime_collected'] - $before['lifetime_collected'], 0.001);
    }

    private function growthRevenue(): array
    {
        Cache::forget('admin:growth:week');

        return app(GrowthAnalyticsService::class)->getGrowthMetrics('week')['revenue'];
    }

    private function coinPurchase(
        User $vendor,
        int $price,
        ?string $paidAt,
        CoinPurchaseStatusEnum $status = CoinPurchaseStatusEnum::COMPLETED,
    ): void {
        DB::table('coin_purchases')->insert([
            'vendor_id' => $vendor->id,
            'pack' => 'starter',
            'price' => $price,
            'coins' => 100,
            'reference' => 'growth_test_'.uniqid(),
            'status' => $status->value,
            'paid_at' => $paidAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function growthHeadline(): array
    {
        Cache::forget('admin:growth:week');

        return app(GrowthAnalyticsService::class)->getGrowthMetrics('week')['headline'];
    }

    private function vendorsJoinedOn(string $date, int $count): void
    {
        User::factory()->count($count)->create([
            'role' => 'vendor',
            'created_at' => Carbon::parse($date.' 10:00:00'),
        ]);
    }

    private function contactClick(User $vendor, ViewSourceEnum $source): void
    {
        $link = app(ContactLinkService::class)->mint($vendor, $source, 'visitor_growth_test');

        ContactClick::create([
            'contact_link_id' => $link->id,
            'vendor_id' => $vendor->id,
            'source' => $source,
            'buyer_identity' => 'visitor_growth_test',
            'is_billable' => true,
            'created_at' => now(),
        ]);
    }
}
