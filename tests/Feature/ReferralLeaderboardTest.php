<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use Tests\TestCase;

class ReferralLeaderboardTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $referralService;

    private ?Category $category = null;

    private int $listingNumber = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->referralService = app(ReferralService::class);

        // Kept at one so fixtures stay cheap; the real bar has its own tests.
        config(['referral.min_products' => 1]);

        Model::preventLazyLoading();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();
    }

    // ==================== Participants ====================

    public function test_total_participants_counts_only_vendors_who_referred_someone(): void
    {
        $this->vendor(referrals: 1);
        $this->vendor(referrals: 4);
        $this->vendor(referrals: 0);
        User::factory()->create(['role' => 'vendor', 'referral_code' => null]);

        $this->assertSame(2, $this->referralService->totalParticipants());
    }

    public function test_holding_a_code_alone_does_not_make_a_vendor_a_participant(): void
    {
        $idle = $this->vendor(referrals: 0);

        $this->assertNotNull($idle->fresh()->referral_code);
        $this->assertSame(0, $this->referralService->totalParticipants());
        $this->assertSame([], $this->referralService->topReferrers());
        $this->assertNull($this->referralService->rankFor($idle));
    }

    public function test_an_unverified_referral_does_not_enrol_a_vendor(): void
    {
        $vendor = $this->vendor(referrals: 0);
        $this->referredUsers($vendor, 2, ['email_verified_at' => null]);

        $this->assertSame(0, $this->referralService->totalParticipants());
        $this->assertNull($this->referralService->rankFor($vendor));
    }

    public function test_a_vendor_joins_the_board_on_their_first_verified_referral(): void
    {
        $vendor = $this->vendor(referrals: 0);

        $this->assertNull($this->referralService->rankFor($vendor));

        $this->referredUsers($vendor, 1);

        $this->assertSame(1, $this->referralService->rankFor($vendor));
        $this->assertSame(1, $this->referralService->totalParticipants());
    }

    // ==================== Top referrers ====================

    public function test_top_referrers_are_ordered_by_referral_count(): void
    {
        $this->vendor(referrals: 1, name: 'Quiet');
        $this->vendor(referrals: 5, name: 'Loud');
        $this->vendor(referrals: 3, name: 'Middling');

        $top = $this->referralService->topReferrers();

        $this->assertSame(['Loud', 'Middling', 'Quiet'], array_column($top, 'name'));
        $this->assertSame([5, 3, 1], array_column($top, 'referral_count'));
        $this->assertSame([1, 2, 3], array_column($top, 'rank'));
    }

    public function test_top_referrers_counts_only_verified_referrals(): void
    {
        $vendor = $this->vendor(referrals: 2, name: 'Padded');
        $this->referredUsers($vendor, 4, ['email_verified_at' => null]);

        $this->assertSame(2, $this->referralService->topReferrers()[0]['referral_count']);
    }

    public function test_top_referrers_is_capped_at_ten(): void
    {
        foreach (range(1, 14) as $index) {
            $this->vendor(referrals: $index, name: "Vendor {$index}");
        }

        $top = $this->referralService->topReferrers();

        $this->assertCount(ReferralService::LEADERBOARD_LIMIT, $top);
        $this->assertSame('Vendor 14', $top[0]['name']);
        $this->assertSame('Vendor 5', $top[9]['name']);
    }

    public function test_top_referrers_honours_a_smaller_limit(): void
    {
        foreach (range(1, 5) as $index) {
            $this->vendor(referrals: $index, name: "Vendor {$index}");
        }

        $this->assertCount(3, $this->referralService->topReferrers(3));
    }

    public function test_ties_go_to_whoever_reached_the_count_first(): void
    {
        $slow = $this->vendor(referrals: 0, name: 'Slow');
        $fast = $this->vendor(referrals: 0, name: 'Fast');

        $this->referredUsers($slow, 2, listedAt: now()->subDays(2));
        $this->referredUsers($fast, 2, listedAt: now()->subDays(9));

        $this->assertSame(['Fast', 'Slow'], array_column($this->referralService->topReferrers(), 'name'));
    }

    public function test_reaching_the_count_is_dated_by_the_listing_not_the_signup(): void
    {
        config(['referral.min_products' => 5]);

        $early = $this->vendor(referrals: 0, name: 'Listed Early');
        $late = $this->vendor(referrals: 0, name: 'Listed Late');

        // The straggler's referral signed up first but only stocked its shelves
        // yesterday; the other signed up last and listed months ago.
        $this->referredUsers($late, 1, ['created_at' => now()->subYear()], products: 5, listedAt: now()->subDay());
        $this->referredUsers($early, 1, ['created_at' => now()->subWeek()], products: 5, listedAt: now()->subMonths(3));

        $this->assertSame(['Listed Early', 'Listed Late'], array_column($this->referralService->topReferrers(), 'name'));
    }

    public function test_reaching_the_count_is_dated_by_the_last_referral_to_qualify(): void
    {
        $finishedFirst = $this->vendor(referrals: 0, name: 'Finished First');
        $finishedLast = $this->vendor(referrals: 0, name: 'Finished Last');

        // Both end on two, but one closed out its pair a month earlier.
        $this->referredUsers($finishedFirst, 1, listedAt: now()->subYear());
        $this->referredUsers($finishedFirst, 1, listedAt: now()->subMonths(2));

        $this->referredUsers($finishedLast, 1, listedAt: now()->subYear());
        $this->referredUsers($finishedLast, 1, listedAt: now()->subMonth());

        $this->assertSame(
            ['Finished First', 'Finished Last'],
            array_column($this->referralService->topReferrers(), 'name')
        );
    }

    public function test_unqualified_referrals_never_influence_the_tiebreak(): void
    {
        config(['referral.min_products' => 5]);

        $leader = $this->vendor(referrals: 0, name: 'Leader');
        $rival = $this->vendor(referrals: 0, name: 'Rival');

        $this->referredUsers($leader, 1, products: 5, listedAt: now()->subMonths(2));
        // A newer, still-unqualified referral must not push the leader's date forward.
        $this->referredUsers($leader, 1, products: 4, listedAt: now()->subMinute());

        $this->referredUsers($rival, 1, products: 5, listedAt: now()->subMonth());

        $top = $this->referralService->topReferrers();

        $this->assertSame(['Leader', 'Rival'], array_column($top, 'name'));
        $this->assertSame([1, 1], array_column($top, 'referral_count'));
    }

    public function test_ordering_is_deterministic_when_even_the_timestamps_tie(): void
    {
        $moment = now()->subDay();

        $first = $this->vendor(referrals: 0, name: 'First');
        $second = $this->vendor(referrals: 0, name: 'Second');

        $this->referredUsers($first, 1, listedAt: $moment);
        $this->referredUsers($second, 1, listedAt: $moment);

        $this->assertLessThan($second->id, $first->id);
        $this->assertSame(['First', 'Second'], array_column($this->referralService->topReferrers(), 'name'));
    }

    public function test_entries_prefer_the_business_name_over_the_account_name(): void
    {
        $vendor = $this->vendor(referrals: 1, name: 'Ada Personal');
        $vendor->forceFill(['business_name' => 'Ada Fabrics'])->save();

        $this->assertSame('Ada Fabrics', $this->referralService->topReferrers()[0]['name']);
    }

    public function test_entries_fall_back_to_the_account_name_when_no_business_name_is_set(): void
    {
        $this->vendor(referrals: 1, name: 'Ada Personal');

        $this->assertSame('Ada Personal', $this->referralService->topReferrers()[0]['name']);
    }

    public function test_the_viewers_own_row_is_flagged(): void
    {
        $viewer = $this->vendor(referrals: 2, name: 'Viewer');
        $this->vendor(referrals: 5, name: 'Rival');

        $top = $this->referralService->topReferrers(viewer: $viewer);

        $this->assertSame([false, true], array_column($top, 'is_you'));
    }

    // ==================== Rank of self ====================

    public function test_rank_reflects_the_vendors_position(): void
    {
        $leader = $this->vendor(referrals: 9);
        $runnerUp = $this->vendor(referrals: 4);
        $last = $this->vendor(referrals: 1);

        $this->assertSame(1, $this->referralService->rankFor($leader));
        $this->assertSame(2, $this->referralService->rankFor($runnerUp));
        $this->assertSame(3, $this->referralService->rankFor($last));
    }

    public function test_rank_is_computed_for_a_vendor_outside_the_top_ten(): void
    {
        foreach (range(1, 12) as $index) {
            $this->vendor(referrals: $index + 5, name: "Vendor {$index}");
        }

        $straggler = $this->vendor(referrals: 2, name: 'Straggler');

        $top = $this->referralService->topReferrers(viewer: $straggler);

        $this->assertNotContains(true, array_column($top, 'is_you'));
        $this->assertSame(13, $this->referralService->rankFor($straggler));
    }

    public function test_tied_vendors_get_distinct_consecutive_ranks(): void
    {
        $earlier = $this->vendor(referrals: 0);
        $later = $this->vendor(referrals: 0);

        $this->referredUsers($earlier, 3, ['created_at' => now()->subDays(5)]);
        $this->referredUsers($later, 3, ['created_at' => now()->subDay()]);

        $this->assertSame(1, $this->referralService->rankFor($earlier));
        $this->assertSame(2, $this->referralService->rankFor($later));
    }

    public function test_rank_is_null_for_a_vendor_who_has_referred_nobody(): void
    {
        $this->vendor(referrals: 3);
        $silent = $this->vendor(referrals: 0);

        $this->assertNull($this->referralService->rankFor($silent));
    }

    public function test_every_rank_matches_the_vendors_position_in_the_board(): void
    {
        $vendors = collect(range(1, 12))->map(fn (int $index): User => $this->vendor(
            referrals: intdiv($index, 3) + 1,
            name: "Vendor {$index}",
        ));

        $board = $this->referralService->topReferrers($vendors->count());

        foreach ($board as $position => $entry) {
            $vendor = $vendors->firstOrFail(fn (User $candidate): bool => $candidate->name === $entry['name']);

            $this->assertSame($position + 1, $this->referralService->rankFor($vendor));
        }
    }

    // ==================== Page ====================

    public function test_page_renders_the_board_and_the_callers_standing(): void
    {
        foreach (range(1, 11) as $index) {
            $this->vendor(referrals: $index + 2, name: "Vendor {$index}");
        }

        $caller = $this->vendor(referrals: 1, name: 'Caller');

        $this->actingAs($caller)->get('/vendor/referral/leaderboard')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Vendor/ReferralLeaderboard', false)
                    ->where('total_participants', 12)
                    ->where('my_rank', 12)
                    ->where('my_referral_count', 1)
                    ->has('top', 10)
                    ->has('top.0', fn (AssertableInertia $entry) => $entry
                        ->where('rank', 1)
                        ->where('name', 'Vendor 11')
                        ->where('referral_count', 13)
                        ->where('is_you', false)
                    )
            );
    }

    public function test_page_flags_the_callers_own_row(): void
    {
        $caller = $this->vendor(referrals: 4, name: 'Caller');
        $this->vendor(referrals: 6, name: 'Rival');

        $this->actingAs($caller)->get('/vendor/referral/leaderboard')
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Vendor/ReferralLeaderboard', false)
                    ->where('top.0.is_you', false)
                    ->where('top.1.is_you', true)
                    ->where('my_rank', 2)
            );
    }

    public function test_page_shows_a_vendor_who_has_referred_nobody_as_unranked(): void
    {
        $newcomer = $this->vendor(referrals: 0);

        $this->actingAs($newcomer)->get('/vendor/referral/leaderboard')
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('Vendor/ReferralLeaderboard', false)
                    ->where('my_rank', null)
                    ->where('my_referral_count', 0)
                    ->where('total_participants', 0)
                    ->has('top', 0)
            );
    }

    public function test_the_board_never_exposes_another_vendors_email(): void
    {
        $caller = $this->vendor(referrals: 1, name: 'Caller');
        $this->vendor(referrals: 4, name: 'Rival');

        $board = $this->referralService->leaderboardFor($caller);

        $this->assertStringNotContainsString('@', json_encode($board, JSON_THROW_ON_ERROR));
    }

    public function test_page_requires_authentication(): void
    {
        $this->get('/vendor/referral/leaderboard')->assertRedirect('/login');
    }

    public function test_page_is_closed_to_non_vendors(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'referral_code' => null]);

        $this->actingAs($customer)->get('/vendor/referral/leaderboard')->assertForbidden();
    }

    // ==================== Cost ====================

    public function test_the_board_costs_the_same_number_of_queries_however_many_vendors_exist(): void
    {
        // The caller stays outside the top ten in both runs, so only the number
        // of vendors changes between the two measurements.
        $caller = $this->vendor(referrals: 1, name: 'Caller');
        $this->manyVendors(10);

        $small = $this->queriesForBoard($caller);

        $this->manyVendors(40, from: 11);

        $this->assertSame(51, $this->referralService->totalParticipants());
        $this->assertSame($small, $this->queriesForBoard($caller));
    }

    public function test_the_board_costs_fewer_queries_when_the_caller_is_on_it(): void
    {
        $caller = $this->vendor(referrals: 30, name: 'Caller');
        $this->manyVendors(10);

        $onBoard = $this->queriesForBoard($caller);

        $overtaken = $this->vendor(referrals: 1, name: 'Overtaken');

        $this->assertSame(2, $onBoard);
        $this->assertSame(3, $this->queriesForBoard($overtaken));
    }

    public function test_a_caller_on_the_board_is_not_ranked_a_second_time(): void
    {
        $caller = $this->vendor(referrals: 5, name: 'Caller');

        $board = $this->referralService->leaderboardFor($caller);

        $this->assertSame(1, $board['top'][0]['rank']);
        $this->assertTrue($board['top'][0]['is_you']);
        $this->assertSame($board['top'][0]['rank'], $board['my_rank']);
        $this->assertSame($board['top'][0]['referral_count'], $board['my_referral_count']);
    }

    private function queriesForBoard(User $caller): int
    {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->referralService->leaderboardFor($caller);

        return $queries;
    }

    private function manyVendors(int $count, int $from = 1): void
    {
        foreach (range($from, $from + $count - 1) as $index) {
            $this->vendor(referrals: 2, name: "Vendor {$index}");
        }
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
