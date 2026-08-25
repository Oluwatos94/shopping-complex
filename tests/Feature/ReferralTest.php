<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $referralService;

    private ?Category $category = null;

    private int $listingNumber = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referralService = app(ReferralService::class);

        config(['referral.min_products' => 1]);
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

    // ==================== Referred count ====================

    public function test_count_includes_only_verified_accounts(): void
    {
        $vendor = $this->vendorWithoutCode();

        $this->referredUsers($vendor, 3);
        $this->referredUsers($vendor, 2, ['email_verified_at' => null]);

        $this->assertSame(3, $this->referralService->referralCountFor($vendor));
    }

    public function test_a_referral_only_counts_once_the_business_has_listed_enough(): void
    {
        config(['referral.min_products' => 5]);

        $vendor = $this->vendorWithoutCode();
        $this->referredUsers($vendor, 1, products: 4);

        $referred = User::query()->where('referred_by', $vendor->id)->firstOrFail();

        $this->assertSame(0, $this->referralService->referralCountFor($vendor));
        $this->assertSame(1, $this->referralService->referralTallyFor($vendor)['referred']);

        $this->listProducts($referred, 1);

        $this->assertSame(1, $this->referralService->referralCountFor($vendor));
    }

    public function test_a_referred_customer_never_counts_however_it_is_dressed_up(): void
    {
        $vendor = $this->vendorWithoutCode();
        $this->referredUsers($vendor, 3, ['role' => 'customer']);

        $this->assertSame(0, $this->referralService->referralCountFor($vendor));
        $this->assertSame(0, $this->referralService->referralTallyFor($vendor)['referred']);
        $this->assertSame([], $this->referralService->recentReferralsFor($vendor));
    }

    public function test_count_is_zero_for_a_vendor_nobody_joined_through(): void
    {
        $this->assertSame(0, $this->referralService->referralCountFor($this->vendorWithoutCode()));
    }

    public function test_count_ignores_users_referred_by_someone_else(): void
    {
        $vendor = $this->vendorWithoutCode();
        $rival = $this->vendorWithoutCode();

        $this->referredUsers($vendor, 2);
        $this->referredUsers($rival, 5);

        $this->assertSame(2, $this->referralService->referralCountFor($vendor));
        $this->assertSame(5, $this->referralService->referralCountFor($rival));
    }

    public function test_count_rises_once_a_referred_signup_verifies_its_email(): void
    {
        $vendor = $this->vendorWithoutCode();
        $code = $this->referralService->codeFor($vendor);

        $this->get('/?'.ReferralService::QUERY_PARAM.'='.$code);
        $this->registerUser('counted@gmail.com');

        $referred = User::where('email', 'counted@gmail.com')->firstOrFail();
        $this->assertSame($vendor->id, $referred->referred_by);

        // A listed business, but the mailbox is still unproven.
        $referred->forceFill(['role' => 'vendor'])->save();
        $this->listProducts($referred, 1);
        $this->assertSame(0, $this->referralService->referralCountFor($vendor));

        $referred->forceFill(['email_verified_at' => now()])->save();

        $this->assertSame(1, $this->referralService->referralCountFor($vendor));
    }

    public function test_recent_referrals_are_newest_first_and_capped(): void
    {
        $vendor = $this->vendorWithoutCode();

        foreach (range(1, 12) as $index) {
            $this->listProducts(User::factory()->create([
                'role' => 'vendor',
                'name' => "Joiner {$index}",
                'referred_by' => $vendor->id,
                'email_verified_at' => now(),
                'created_at' => now()->subDays(20 - $index),
            ]), 1);
        }

        $recent = $this->referralService->recentReferralsFor($vendor);

        $this->assertCount(ReferralService::RECENT_LIMIT, $recent);
        $this->assertSame('Joiner 12', $recent[0]['name']);
        $this->assertSame(['name', 'email', 'joined_at', 'products_count'], array_keys($recent[0]));
    }

    public function test_recent_referrals_report_how_much_each_joiner_has_listed(): void
    {
        $vendor = $this->vendorWithoutCode();
        $category = Category::factory()->create();

        $listed = User::factory()->create([
            'role' => 'vendor',
            'name' => 'Listed Plenty',
            'referred_by' => $vendor->id,
            'email_verified_at' => now(),
            'created_at' => now()->subDay(),
        ]);
        Product::factory()->count(6)->create(['vendor_id' => $listed->id, 'category_id' => $category->id]);

        User::factory()->create([
            'role' => 'vendor',
            'name' => 'Listed Nothing',
            'referred_by' => $vendor->id,
            'email_verified_at' => now(),
            'created_at' => now()->subDays(2),
        ]);

        $recent = $this->referralService->recentReferralsFor($vendor);

        $this->assertSame(['Listed Plenty', 'Listed Nothing'], array_column($recent, 'name'));
        $this->assertSame([6, 0], array_column($recent, 'products_count'));
    }

    public function test_recent_referrals_omit_unverified_accounts(): void
    {
        $vendor = $this->vendorWithoutCode();

        $this->listProducts(User::factory()->create([
            'role' => 'vendor',
            'name' => 'Verified Joiner',
            'email' => 'verified-joiner@gmail.com',
            'referred_by' => $vendor->id,
            'email_verified_at' => now(),
        ]), 1);
        $this->referredUsers($vendor, 1, ['email_verified_at' => null]);

        $recent = $this->referralService->recentReferralsFor($vendor);

        $this->assertCount(1, $recent);
        $this->assertSame('Verified Joiner', $recent[0]['name']);
        $this->assertSame('verified-joiner@gmail.com', $recent[0]['email']);
    }

    public function test_recent_referrals_name_each_joiner_by_their_business(): void
    {
        $vendor = $this->vendorWithoutCode();

        $this->listProducts(User::factory()->create([
            'role' => 'vendor',
            'name' => 'Ada Personal',
            'business_name' => 'Ada Fabrics',
            'referred_by' => $vendor->id,
            'email_verified_at' => now(),
        ]), 1);

        $recent = $this->referralService->recentReferralsFor($vendor);

        $this->assertSame('Ada Fabrics', $recent[0]['name']);
    }

    public function test_count_and_breakdown_cost_a_fixed_number_of_queries(): void
    {
        $vendor = $this->vendorWithoutCode();
        $this->referredUsers($vendor, 15);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->referralService->referralCountFor($vendor);
        $this->referralService->recentReferralsFor($vendor);

        $this->assertSame(2, $queries);
    }

    public function test_endpoint_returns_the_count_and_breakdown(): void
    {
        $vendor = $this->vendorWithoutCode();
        $this->referredUsers($vendor, 4);
        $this->referredUsers($vendor, 1, ['email_verified_at' => null]);

        $this->actingAs($vendor)->getJson('/vendor/referral')
            ->assertOk()
            ->assertJsonPath('count', 4)
            ->assertJsonCount(4, 'recent')
            ->assertJsonStructure(['count', 'recent' => [['name', 'email', 'joined_at', 'products_count']]]);
    }

    public function test_dashboard_exposes_the_count(): void
    {
        $vendor = $this->vendorWithoutCode();
        $this->referredUsers($vendor, 3);
        $this->referredUsers($vendor, 2, ['email_verified_at' => null]);

        $this->actingAs($vendor)->get('/vendor')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Vendor/Dashboard', false)
                ->where('referral.count', 3)
                ->has('referral.recent', 3)
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function referredUsers(
        User $referrer,
        int $count,
        array $attributes = [],
        int $products = 1,
        ?CarbonInterface $listedAt = null,
    ): void {
        User::factory()->count($count)->create(array_merge([
            'role' => 'vendor',
            'referred_by' => $referrer->id,
            'email_verified_at' => now(),
        ], $attributes))->each(fn (User $vendor) => $this->listProducts($vendor, $products, $listedAt));
    }

    private function listProducts(User $vendor, int $count, ?CarbonInterface $listedAt = null): void
    {
        if ($count < 1) {
            return;
        }

        $this->category ??= Category::factory()->create();

        // products.slug is unique and the factory derives it from random words,
        // which collides once fixtures run into the hundreds.
        Product::factory()
            ->count($count)
            ->sequence(fn () => [
                'name' => 'Listing '.++$this->listingNumber,
                'slug' => 'listing-'.$this->listingNumber,
            ])
            ->create(array_filter([
                'vendor_id' => $vendor->id,
                'category_id' => $this->category->id,
                'created_at' => $listedAt,
            ], fn ($value) => $value !== null));
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
