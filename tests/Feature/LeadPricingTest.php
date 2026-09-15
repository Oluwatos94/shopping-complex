<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Billing\Services\LeadBillingService;
use ModulesShoppingComplex\Billing\Services\LeadPricingService;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class LeadPricingTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER = '2348011112222';

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.leads.default_cost' => 5, 'billing.coins.expiry_months' => 12]);
    }

    private function pricing(): LeadPricingService
    {
        return app(LeadPricingService::class);
    }

    private function category(string $slug, int $cost): Category
    {
        return Category::create(['name' => ucfirst($slug), 'slug' => $slug.'-'.uniqid(), 'lead_coin_cost' => $cost]);
    }

    private function vendor(?Category $category = null, ?int $override = null): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'whatsapp_number' => '08031234567',
            'category_id' => $category?->id,
            'lead_coin_cost_override' => $override,
        ]);
    }

    // ==================== Resolution ====================

    public function test_the_category_tier_prices_the_lead(): void
    {
        $vendor = $this->vendor($this->category('electronics', 10));

        $this->assertSame(10, $this->pricing()->costFor($vendor));
    }

    public function test_a_vendor_override_beats_the_category_tier(): void
    {
        $vendor = $this->vendor($this->category('services', 20), override: 7);

        $this->assertSame(7, $this->pricing()->costFor($vendor));
    }

    public function test_a_vendor_with_no_category_falls_back_to_the_default(): void
    {
        $this->assertSame(5, $this->pricing()->costFor($this->vendor()));
    }

    public function test_the_price_is_floored_at_one(): void
    {
        // A zero override must still cost something — a lead is never free.
        $this->assertSame(1, $this->pricing()->costFor($this->vendor(override: 0)));
    }

    public function test_the_price_comes_off_the_vendors_category_not_the_search(): void
    {
        // The vendor's own category is the only input; there is no buyer search here.
        $vendor = $this->vendor($this->category('automotive', 20));

        $this->assertSame(20, $this->pricing()->costFor($vendor));
    }

    // ==================== Seeded tiers ====================

    public function test_the_seeded_categories_carry_the_published_tiers(): void
    {
        $this->seed(CategorySeeder::class);

        $costOf = fn (string $slug): int => (int) Category::where('slug', $slug)->value('lead_coin_cost');

        $this->assertSame(5, $costOf('groceries-food'));
        $this->assertSame(5, $costOf('fashion-clothing'));
        $this->assertSame(10, $costOf('electronics-repairs'));
        $this->assertSame(10, $costOf('art-gallery'));
        $this->assertSame(20, $costOf('services'));
        $this->assertSame(20, $costOf('automotive-tools'));
        $this->assertSame(30, $costOf('real-estate-property'));
    }

    // ==================== Charging through the real debit path ====================

    public function test_a_lead_charges_the_vendors_category_rate(): void
    {
        Event::fake([VendorContactClicked::class]);

        $vendor = $this->vendor($this->category('electronics', 10));
        app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $lead = $this->billLead($vendor);

        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead?->state);
        $this->assertSame(10, $lead?->coins_charged);
        $this->assertSame(90, app(CoinWalletService::class)->balance($vendor));
    }

    public function test_changing_a_category_rate_does_not_alter_leads_already_recorded(): void
    {
        Event::fake([VendorContactClicked::class]);

        $category = $this->category('services', 20);
        $vendor = $this->vendor($category);
        app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $first = $this->billLead($vendor);
        $this->assertSame(20, $first?->coins_charged);

        // Re-price the category, then a fresh lead 31 days later.
        $category->update(['lead_coin_cost' => 8]);
        $this->travel(31)->days();
        $second = $this->billLead($vendor);

        $this->assertSame(8, $second?->coins_charged);
        // The first lead's recorded charge is untouched.
        $this->assertSame(20, $first->fresh()?->coins_charged);
    }

    private function billLead(User $vendor): ?BillableLead
    {
        $links = app(ContactLinkService::class);
        $click = $links->recordClick($links->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER));

        return app(LeadBillingService::class)->bill($click);
    }
}
