<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $referralService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referralService = app(ReferralService::class);
    }

    private function vendorWithoutCode(): User
    {
        return User::factory()->create(['role' => 'vendor', 'referral_code' => null]);
    }

    // ==================== Code generation ====================

    public function test_generated_codes_are_unique_across_users(): void
    {
        $codes = [];

        foreach (range(1, 25) as $ignored) {
            $codes[] = $this->referralService->codeFor($this->vendorWithoutCode());
        }

        $this->assertCount(25, array_unique($codes));
    }

    public function test_generated_code_uses_the_configured_length_and_unambiguous_alphabet(): void
    {
        config(['referral.code_length' => 10]);

        $code = $this->referralService->codeFor($this->vendorWithoutCode());

        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{10}$/', $code);
    }

    // ==================== Get-or-create ====================

    public function test_code_for_persists_the_minted_code(): void
    {
        $vendor = $this->vendorWithoutCode();

        $code = $this->referralService->codeFor($vendor);

        $this->assertSame($code, $vendor->referral_code);
        $this->assertDatabaseHas('users', ['id' => $vendor->id, 'referral_code' => $code]);
    }

    public function test_code_for_is_idempotent(): void
    {
        $vendor = $this->vendorWithoutCode();

        $first = $this->referralService->codeFor($vendor);
        $second = $this->referralService->codeFor($vendor);
        $third = $this->referralService->codeFor($vendor->fresh());

        $this->assertSame($first, $second);
        $this->assertSame($first, $third);
    }

    public function test_endpoint_returns_code_and_share_link_and_never_mints_twice(): void
    {
        $vendor = $this->vendorWithoutCode();

        $first = $this->actingAs($vendor)->getJson('/vendor/referral');
        $second = $this->actingAs($vendor)->getJson('/vendor/referral');

        $first->assertOk()->assertJsonStructure(['code', 'share_url']);

        $code = $first->json('code');

        $this->assertSame($code, $second->json('code'));
        $this->assertSame(url('/register').'?ref='.$code, $first->json('share_url'));
        $this->assertSame($code, $vendor->fresh()->referral_code);
    }

    public function test_endpoint_requires_authentication(): void
    {
        $this->getJson('/vendor/referral')->assertUnauthorized();
    }

    public function test_endpoint_is_closed_to_non_vendors(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'referral_code' => null]);

        $this->actingAs($customer)->getJson('/vendor/referral')->assertForbidden();

        $this->assertNull($customer->fresh()->referral_code);
    }

    public function test_dashboard_exposes_the_persisted_code_not_a_derived_one(): void
    {
        $vendor = $this->vendorWithoutCode();

        $response = $this->actingAs($vendor)->get('/vendor');

        $response->assertOk();

        $code = $vendor->fresh()->referral_code;

        $this->assertNotNull($code);
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                // No existence check: pages live in resources/ts/pages, not the
                // resources/js/Pages that inertia.testing.page_paths defaults to.
                ->component('Vendor/Dashboard', false)
                ->where('referral.code', $code)
                ->where('referral.link', $this->referralService->shareUrl($code))
        );
    }

    // ==================== Existing-user backfill ====================

    public function test_backfill_gives_every_existing_vendor_a_code(): void
    {
        $vendors = collect(range(1, 3))->map(fn () => $this->vendorWithoutCode());
        $customer = User::factory()->create(['role' => 'customer', 'referral_code' => null]);

        $backfilled = $this->referralService->backfillVendorCodes(chunkSize: 2);

        $this->assertSame(3, $backfilled);
        $this->assertCount(3, $vendors->map(fn (User $vendor) => $vendor->fresh()->referral_code)->unique()->filter());
        $this->assertNull($customer->fresh()->referral_code);
    }

    public function test_backfill_leaves_existing_codes_untouched(): void
    {
        $vendor = $this->vendorWithoutCode();
        $code = $this->referralService->codeFor($vendor);

        $backfilled = $this->referralService->backfillVendorCodes();

        $this->assertSame(0, $backfilled);
        $this->assertSame($code, $vendor->fresh()->referral_code);
    }

    // ==================== Signup capture ====================

    public function test_registering_through_a_referral_link_sets_referred_by(): void
    {
        $referrer = $this->vendorWithoutCode();
        $code = $this->referralService->codeFor($referrer);

        $this->get('/?'.http_build_query(['ref' => $code]))->assertOk();

        $this->registerUser('referred@gmail.com');

        $this->assertSame($referrer->id, User::where('email', 'referred@gmail.com')->value('referred_by'));
    }

    public function test_referral_code_is_captured_from_any_page_and_is_case_insensitive(): void
    {
        $referrer = $this->vendorWithoutCode();
        $code = $this->referralService->codeFor($referrer);

        $this->get('/register?ref='.strtolower($code))->assertOk();

        $this->registerUser('lowercase@gmail.com');

        $this->assertSame($referrer->id, User::where('email', 'lowercase@gmail.com')->value('referred_by'));
    }

    public function test_unknown_referral_code_is_ignored(): void
    {
        $this->get('/?ref=NOSUCHCODE')->assertOk();

        $this->registerUser('unknown@gmail.com');

        $this->assertNull(User::where('email', 'unknown@gmail.com')->value('referred_by'));
    }

    public function test_registering_without_a_referral_link_leaves_referred_by_null(): void
    {
        $this->registerUser('organic@gmail.com');

        $this->assertNull(User::where('email', 'organic@gmail.com')->value('referred_by'));
    }

    public function test_pending_referral_is_consumed_so_it_cannot_attribute_twice(): void
    {
        $referrer = $this->vendorWithoutCode();
        $code = $this->referralService->codeFor($referrer);

        $this->get('/?ref='.$code)->assertOk();
        $this->registerUser('first@gmail.com');

        $this->post('/logout');
        $this->registerUser('second@gmail.com');

        $this->assertSame($referrer->id, User::where('email', 'first@gmail.com')->value('referred_by'));
        $this->assertNull(User::where('email', 'second@gmail.com')->value('referred_by'));
    }

    // ==================== Invalid attribution ====================

    public function test_self_referral_is_ignored(): void
    {
        $vendor = $this->vendorWithoutCode();
        $code = $this->referralService->codeFor($vendor);

        $this->assertFalse($this->referralService->attachReferrer($vendor, $code));
        $this->assertNull($vendor->fresh()->referred_by);
    }

    public function test_referrer_cannot_be_reassigned_once_set(): void
    {
        $firstReferrer = $this->vendorWithoutCode();
        $secondReferrer = $this->vendorWithoutCode();
        $user = User::factory()->create(['role' => 'customer']);

        $this->assertTrue($this->referralService->attachReferrer($user, $this->referralService->codeFor($firstReferrer)));
        $this->assertFalse($this->referralService->attachReferrer($user, $this->referralService->codeFor($secondReferrer)));

        $this->assertSame($firstReferrer->id, $user->fresh()->referred_by);
    }

    public function test_malformed_referral_codes_are_rejected(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        foreach (['', 'ab', 'has space', "DROP'--", str_repeat('A', 33)] as $code) {
            $this->assertFalse($this->referralService->attachReferrer($user, $code));
        }

        $this->assertNull($user->fresh()->referred_by);
    }

    // ==================== Vendor creation & relationships ====================

    public function test_relationships_link_referrer_and_referrals(): void
    {
        $referrer = $this->vendorWithoutCode();
        $code = $this->referralService->codeFor($referrer);

        $referred = User::factory()->count(2)->create(['role' => 'customer'])
            ->each(fn (User $user) => $this->referralService->attachReferrer($user, $code));

        $this->assertSame(2, $referrer->referrals()->count());
        $this->assertSame($referrer->id, $referred->first()->fresh()->referrer->id);
    }

    private function registerUser(string $email): void
    {
        $this->post('/register', [
            'name' => 'Referred User',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'customer',
        ])->assertRedirect('/email/verify');
    }
}
