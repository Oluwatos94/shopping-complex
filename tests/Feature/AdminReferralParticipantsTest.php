<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Analytics\Services\AdminAnalyticsService;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Models\VendorOnboarding;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use Tests\TestCase;

class AdminReferralParticipantsTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $referralService;

    private User $admin;

    private ?Category $category = null;

    private int $listingNumber = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referralService = app(ReferralService::class);
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Kept at one so fixtures stay cheap; the real bar has its own tests.
        config(['referral.min_products' => 1]);

        Model::preventLazyLoading();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();
    }

    // ==================== Authorization ====================

    public function test_the_roster_is_closed_to_guests(): void
    {
        $this->getJson('/admin/referral/participants')->assertUnauthorized();
    }

    public function test_the_roster_is_closed_to_customers_and_vendors(): void
    {
        foreach (['customer', 'vendor'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->getJson('/admin/referral/participants')
                ->assertForbidden();
        }
    }

    public function test_a_participants_detail_is_closed_to_non_admins(): void
    {
        $participant = $this->vendor(referrals: 2);

        $this->actingAs(User::factory()->create(['role' => 'vendor']))
            ->getJson("/admin/referral/participants/{$participant->id}")
            ->assertForbidden();
    }

    // ==================== Roster ====================

    public function test_the_roster_lists_participants_ranked_by_referral_count(): void
    {
        $this->vendor(referrals: 2, name: 'Middling');
        $this->vendor(referrals: 7, name: 'Leader');
        $this->vendor(referrals: 1, name: 'Trailing');

        $response = $this->actingAs($this->admin)->getJson('/admin/referral/participants');

        $response->assertOk()
            ->assertJsonPath('total_participants', 3)
            ->assertJsonPath('participants.total', 3)
            ->assertJsonPath('participants.data.0.name', 'Leader')
            ->assertJsonPath('participants.data.0.rank', 1)
            ->assertJsonPath('participants.data.0.referral_count', 7)
            ->assertJsonPath('participants.data.2.name', 'Trailing')
            ->assertJsonPath('participants.data.2.rank', 3)
            ->assertJsonStructure([
                'total_participants',
                'search',
                'participants' => [
                    'data' => [['user_id', 'name', 'account_name', 'email', 'referral_count', 'rank', 'joined_at']],
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_the_roster_excludes_vendors_who_have_referred_nobody(): void
    {
        $this->vendor(referrals: 3, name: 'Participating');
        $this->vendor(referrals: 0, name: 'Idle');
        $unverified = $this->vendor(referrals: 0, name: 'Unverified Only');
        $this->referredUsers($unverified, 2, ['email_verified_at' => null]);
        User::factory()->create(['role' => 'customer']);

        $this->actingAs($this->admin)->getJson('/admin/referral/participants')
            ->assertOk()
            ->assertJsonPath('total_participants', 1)
            ->assertJsonCount(1, 'participants.data')
            ->assertJsonPath('participants.data.0.name', 'Participating');
    }

    public function test_the_roster_excludes_a_vendor_whose_referrals_have_not_listed_enough(): void
    {
        config(['referral.min_products' => 5]);

        $counted = $this->vendor(referrals: 0, name: 'Counted');
        $this->referredUsers($counted, 1, products: 5);

        $waiting = $this->vendor(referrals: 0, name: 'Still Waiting');
        $this->referredUsers($waiting, 3, products: 4);

        $this->actingAs($this->admin)->getJson('/admin/referral/participants')
            ->assertOk()
            ->assertJsonPath('total_participants', 1)
            ->assertJsonCount(1, 'participants.data')
            ->assertJsonPath('participants.data.0.name', 'Counted')
            ->assertJsonPath('participants.data.0.referral_count', 1);

        $this->actingAs($this->admin)->getJson("/admin/referral/participants/{$waiting->id}")
            ->assertOk()
            ->assertJsonPath('referral_count', 0)
            ->assertJsonPath('referred_count', 3)
            ->assertJsonPath('min_products', 5)
            ->assertJsonCount(3, 'referrals');
    }

    public function test_the_roster_prefers_the_business_name_and_keeps_the_account_name(): void
    {
        $vendor = $this->vendor(referrals: 1, name: 'Ada Personal');
        $vendor->forceFill(['business_name' => 'Ada Fabrics'])->save();

        $this->actingAs($this->admin)->getJson('/admin/referral/participants')
            ->assertOk()
            ->assertJsonPath('participants.data.0.name', 'Ada Fabrics')
            ->assertJsonPath('participants.data.0.account_name', 'Ada Personal')
            ->assertJsonPath('participants.data.0.email', $vendor->email);
    }

    public function test_the_roster_paginates(): void
    {
        foreach (range(1, 7) as $index) {
            $this->vendor(referrals: $index, name: "Vendor {$index}");
        }

        $this->actingAs($this->admin)->getJson('/admin/referral/participants?per_page=3&page=2')
            ->assertOk()
            ->assertJsonPath('participants.current_page', 2)
            ->assertJsonPath('participants.last_page', 3)
            ->assertJsonCount(3, 'participants.data')
            ->assertJsonPath('participants.data.0.rank', 4)
            ->assertJsonPath('participants.data.0.name', 'Vendor 4');
    }

    public function test_the_roster_clamps_an_absurd_page_size(): void
    {
        $this->vendor(referrals: 1);

        $this->actingAs($this->admin)->getJson('/admin/referral/participants?per_page=5000')
            ->assertOk()
            ->assertJsonPath('participants.per_page', 100);
    }

    public function test_the_roster_renders_as_an_inertia_page(): void
    {
        $this->vendor(referrals: 4, name: 'Leader');

        $this->actingAs($this->admin)->get('/admin/referral/participants')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Admin/ReferralParticipants', false)
                    ->where('total_participants', 1)
                    ->where('search', '')
                    ->has('participants.data', 1)
            );
    }

    // ==================== Search ====================

    public function test_search_matches_name_business_name_or_email(): void
    {
        $vendor = $this->vendor(referrals: 3, name: 'Chinelo Okeke');
        $vendor->forceFill(['business_name' => 'Zenith Textiles'])->save();
        $this->vendor(referrals: 5, name: 'Somebody Else');

        foreach (['Chinelo', 'Zenith', $vendor->email] as $term) {
            $this->actingAs($this->admin)->getJson('/admin/referral/participants?search='.urlencode($term))
                ->assertOk()
                ->assertJsonCount(1, 'participants.data')
                ->assertJsonPath('participants.data.0.user_id', $vendor->id);
        }
    }

    public function test_a_searched_row_keeps_its_rank_on_the_full_board(): void
    {
        foreach (range(1, 5) as $index) {
            $this->vendor(referrals: $index + 5, name: "Vendor {$index}");
        }

        $straggler = $this->vendor(referrals: 1, name: 'Straggler');

        $this->actingAs($this->admin)->getJson('/admin/referral/participants?search=Straggler')
            ->assertOk()
            ->assertJsonCount(1, 'participants.data')
            ->assertJsonPath('participants.data.0.rank', 6)
            ->assertJsonPath('total_participants', 6);

        $this->assertSame(6, $this->referralService->rankFor($straggler));
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        $literal = $this->vendor(referrals: 2, name: 'Discount 50% Store');
        $this->vendor(referrals: 4, name: 'Somebody Else');
        $this->vendor(referrals: 1, name: 'Another One');

        // A bare "%" matches the one name that literally contains it, not all three.
        $this->actingAs($this->admin)->getJson('/admin/referral/participants?search='.urlencode('%'))
            ->assertOk()
            ->assertJsonCount(1, 'participants.data')
            ->assertJsonPath('participants.data.0.user_id', $literal->id);

        $this->actingAs($this->admin)->getJson('/admin/referral/participants?search='.urlencode('50%'))
            ->assertOk()
            ->assertJsonCount(1, 'participants.data')
            ->assertJsonPath('participants.data.0.user_id', $literal->id);
    }

    public function test_search_reports_the_term_back(): void
    {
        $this->actingAs($this->admin)->getJson('/admin/referral/participants?search=%20nobody%20')
            ->assertOk()
            ->assertJsonPath('search', 'nobody')
            ->assertJsonCount(0, 'participants.data');
    }

    // ==================== Drill-in ====================

    public function test_a_participants_detail_carries_the_vendor_application_and_their_standing(): void
    {
        $leader = $this->vendor(referrals: 3, name: 'Leader');
        $this->vendor(referrals: 9, name: 'Bigger Leader');

        VendorOnboarding::create([
            'user_id' => $leader->id,
            'legal_entity_name' => 'Leader Ltd',
            'status' => VendorOnboardingStatusEnum::APPROVED,
            'current_step' => 4,
            'agreed_to_terms' => true,
        ]);

        $this->actingAs($this->admin)->getJson("/admin/referral/participants/{$leader->id}")
            ->assertOk()
            ->assertJsonPath('user_id', $leader->id)
            ->assertJsonPath('user.email', $leader->email)
            ->assertJsonPath('legal_entity_name', 'Leader Ltd')
            ->assertJsonPath('status', VendorOnboardingStatusEnum::APPROVED->value)
            ->assertJsonPath('referral_count', 3)
            ->assertJsonPath('rank', 2)
            ->assertJsonCount(3, 'referrals')
            ->assertJsonStructure(['referrals' => [['name', 'joined_at', 'products_count']]]);
    }

    public function test_the_drill_in_reports_how_much_each_referred_vendor_has_listed(): void
    {
        $participant = $this->vendor(referrals: 0);
        $category = Category::factory()->create();

        $stocked = User::factory()->create([
            'role' => 'vendor',
            'name' => 'Stocked Vendor',
            'referred_by' => $participant->id,
            'email_verified_at' => now(),
            'created_at' => now()->subDay(),
        ]);
        Product::factory()->count(5)->create(['vendor_id' => $stocked->id, 'category_id' => $category->id]);

        User::factory()->create([
            'role' => 'vendor',
            'name' => 'Empty Vendor',
            'referred_by' => $participant->id,
            'email_verified_at' => now(),
            'created_at' => now()->subDays(2),
        ]);

        $referrals = $this->actingAs($this->admin)
            ->getJson("/admin/referral/participants/{$participant->id}")
            ->assertOk()
            ->json('referrals');

        $this->assertSame(['Stocked Vendor', 'Empty Vendor'], array_column($referrals, 'name'));
        $this->assertSame([5, 0], array_column($referrals, 'products_count'));
    }

    public function test_a_participants_detail_works_without_an_onboarding_record(): void
    {
        $vendor = $this->vendor(referrals: 1);

        $this->actingAs($this->admin)->getJson("/admin/referral/participants/{$vendor->id}")
            ->assertOk()
            ->assertJsonPath('status', 'registered')
            ->assertJsonPath('referral_count', 1)
            ->assertJsonPath('rank', 1);
    }

    public function test_a_vendor_who_has_referred_nobody_is_unranked_in_the_drill_in(): void
    {
        $vendor = $this->vendor(referrals: 0);

        $this->actingAs($this->admin)->getJson("/admin/referral/participants/{$vendor->id}")
            ->assertOk()
            ->assertJsonPath('referral_count', 0)
            ->assertJsonPath('rank', null)
            ->assertJsonPath('referrals', []);
    }

    public function test_a_detail_is_not_served_for_a_non_vendor(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($this->admin)->getJson("/admin/referral/participants/{$customer->id}")
            ->assertNotFound();
    }

    public function test_the_drill_in_omits_the_referred_users_contact_details(): void
    {
        $vendor = $this->vendor(referrals: 0);
        $this->referredUsers($vendor, 2);

        $response = $this->actingAs($this->admin)->getJson("/admin/referral/participants/{$vendor->id}")->assertOk();

        $this->assertStringNotContainsString('@', json_encode($response->json('referrals'), JSON_THROW_ON_ERROR));
    }

    // ==================== Cost ====================

    public function test_the_roster_costs_the_same_number_of_queries_however_many_participants_exist(): void
    {
        foreach (range(1, 3) as $index) {
            $this->vendor(referrals: $index, name: "Vendor {$index}");
        }

        $small = $this->queriesForRoster();

        foreach (range(4, 25) as $index) {
            $this->vendor(referrals: $index, name: "Vendor {$index}");
        }

        $this->assertSame($small, $this->queriesForRoster());
    }

    public function test_a_drill_in_costs_the_same_number_of_queries_however_many_referrals(): void
    {
        $admin = app(AdminAnalyticsService::class);
        $quiet = $this->vendor(referrals: 1);
        $busy = $this->vendor(referrals: 30);

        $this->assertSame(
            $this->queriesFor(fn () => $admin->getCampaignParticipant($quiet->fresh())),
            $this->queriesFor(fn () => $admin->getCampaignParticipant($busy->fresh())),
        );
    }

    private function queriesForRoster(): int
    {
        return $this->queriesFor(fn () => app(ReferralService::class)->participants(null, 20));
    }

    private function queriesFor(callable $work): int
    {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $work();

        return $queries;
    }

    private function vendor(int $referrals, ?string $name = null): User
    {
        $attributes = ['role' => 'vendor', 'referral_code' => null];

        if ($name !== null) {
            $attributes['name'] = $name;
        }

        $vendor = User::factory()->create($attributes);

        $this->referralService->codeFor($vendor);

        if ($referrals > 0) {
            $this->referredUsers($vendor, $referrals);
        }

        return $vendor;
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
}
