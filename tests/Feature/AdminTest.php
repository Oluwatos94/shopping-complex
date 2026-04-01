<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;
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
        $onboarding = VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/vendors/{$this->vendor->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('message', 'Vendor approved successfully.');

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
            ->postJson("/admin/vendors/{$this->vendor->id}/reject", [
                'rejection_reason' => 'Documents are incomplete.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('message', 'Vendor rejected successfully.');

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
            ->postJson("/admin/vendors/{$this->vendor->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No pending application found for this vendor.');
    }

    public function test_cannot_approve_already_approved_vendor(): void
    {
        VendorOnboarding::create([
            'user_id' => $this->vendor->id,
            'status' => VendorOnboardingStatusEnum::PENDING_REVIEW,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        // First approval succeeds
        $this->actingAs($this->admin)
            ->postJson("/admin/vendors/{$this->vendor->id}/approve")
            ->assertStatus(200);

        // Second approval (simulates race condition at application level) must fail
        $this->actingAs($this->admin)
            ->postJson("/admin/vendors/{$this->vendor->id}/approve")
            ->assertStatus(422)
            ->assertJsonPath('message', 'No pending application found for this vendor.');
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
}
