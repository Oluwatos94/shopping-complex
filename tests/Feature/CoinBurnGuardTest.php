<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use Tests\Support\NullWhatsAppSender;
use Tests\TestCase;

class CoinBurnGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.leads.default_cost' => 10,
            'billing.coins.expiry_months' => 12,
            'billing.guards.velocity_buyer.vendors' => 3,
            'billing.guards.velocity_buyer.minutes' => 5,
            'billing.guards.velocity_ip.identities' => 3,
            'billing.guards.velocity_ip.minutes' => 10,
            'billing.guards.rate_limit.max' => 3,
            'billing.guards.rate_limit.seconds' => 60,
        ]);

        $this->app->instance(WhatsAppSender::class, new NullWhatsAppSender);
    }

    private function vendor(string $whatsapp = '08031234567', ?int $cap = null): User
    {
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Crystal Wears',
            'whatsapp_number' => $whatsapp,
            'daily_coin_cap' => $cap,
        ]);

        app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 1000);

        return $vendor;
    }

    private function hit(User $vendor, string $buyer, ?string $ip = '10.0.0.1'): BillableLead
    {
        $links = app(ContactLinkService::class);
        $links->recordClick($links->mint($vendor, ViewSourceEnum::WHATSAPP, $buyer), $ip);

        return BillableLead::where('vendor_id', $vendor->id)
            ->where('buyer_identity', $buyer)
            ->latest('id')
            ->firstOrFail();
    }

    // ==================== Self-click ====================

    public function test_a_vendor_clicking_their_own_link_is_never_charged(): void
    {
        $vendor = $this->vendor('08031234567');

        $lead = $this->hit($vendor, '2348031234567'); // same number, E.164 form

        $this->assertSame(BillableLeadStateEnum::UNBILLED, $lead->state);
        $this->assertSame(LeadUnbilledReasonEnum::SELF_CLICK, $lead->unbilled_reason);
        $this->assertSame(0, $lead->coins_charged);
        $this->assertSame(1000, app(CoinWalletService::class)->balance($vendor));
    }

    // ==================== Daily cap ====================

    public function test_a_vendor_is_never_charged_beyond_their_daily_cap(): void
    {
        $vendor = $this->vendor(cap: 20); // two 10-coin leads a day, no more

        $this->hit($vendor, '2348010000001', '10.0.0.1');
        $this->hit($vendor, '2348010000002', '10.0.0.2');
        $third = $this->hit($vendor, '2348010000003', '10.0.0.3');

        $this->assertSame(LeadUnbilledReasonEnum::DAILY_CAP, $third->unbilled_reason);
        $this->assertSame(0, $third->coins_charged);
        $this->assertSame(980, app(CoinWalletService::class)->balance($vendor)); // exactly the cap spent
    }

    public function test_the_cap_resets_the_next_day(): void
    {
        $vendor = $this->vendor(cap: 10);

        $this->hit($vendor, '2348010000001'); // charged
        $blocked = $this->hit($vendor, '2348010000002');
        $this->assertSame(LeadUnbilledReasonEnum::DAILY_CAP, $blocked->unbilled_reason);

        $this->travel(1)->day();
        $next = $this->hit($vendor, '2348010000003');

        $this->assertSame(BillableLeadStateEnum::CHARGED, $next->state);
    }

    // ==================== Velocity ====================

    public function test_a_buyer_fanning_out_to_many_vendors_is_flagged(): void
    {
        $a = $this->vendor('08030000001');
        $b = $this->vendor('08030000002');
        $c = $this->vendor('08030000003');
        $buyer = '2348011112222';

        $this->hit($a, $buyer);
        $this->hit($b, $buyer);
        $third = $this->hit($c, $buyer); // third distinct vendor trips the guard

        $this->assertSame(LeadUnbilledReasonEnum::VELOCITY_BUYER, $third->unbilled_reason);
        $this->assertSame(0, $third->coins_charged);
    }

    public function test_one_ip_spawning_many_identities_is_flagged(): void
    {
        $vendor = $this->vendor();

        $this->hit($vendor, '2348010000001', '203.0.113.9');
        $this->hit($vendor, '2348010000002', '203.0.113.9');
        $third = $this->hit($vendor, '2348010000003', '203.0.113.9');

        $this->assertSame(LeadUnbilledReasonEnum::VELOCITY_IP, $third->unbilled_reason);
        $this->assertSame(0, $third->coins_charged);
    }

    // ==================== Review surface ====================

    public function test_flagged_leads_are_queryable_with_their_reason(): void
    {
        $vendor = $this->vendor('08031234567');
        $this->hit($vendor, '2348031234567');                                // self-click
        $this->hit($this->vendor('08030000009', cap: 0), '2348019999999');   // daily cap (0), not "flagged" abuse

        $flagged = BillableLead::flagged()->get();

        $this->assertCount(1, $flagged);
        $this->assertSame(LeadUnbilledReasonEnum::SELF_CLICK, $flagged->first()->unbilled_reason);
    }

    // ==================== Buyer is never degraded ====================

    public function test_a_blocked_click_still_redirects_the_buyer(): void
    {
        $vendor = $this->vendor('08031234567');
        $links = app(ContactLinkService::class);
        $token = $links->mint($vendor, ViewSourceEnum::WHATSAPP, '2348031234567')->token;

        $this->get(route('contact.redirect', ['token' => $token]))->assertRedirect();

        $lead = BillableLead::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame(LeadUnbilledReasonEnum::SELF_CLICK, $lead->unbilled_reason);
    }

    // ==================== Rate limit ====================

    public function test_the_redirect_is_rate_limited_per_identity(): void
    {
        $vendor = $this->vendor();
        $links = app(ContactLinkService::class);
        $token = $links->mint($vendor, ViewSourceEnum::WHATSAPP, '2348011112222')->token;

        for ($i = 0; $i < 5; $i++) {
            $this->get(route('contact.redirect', ['token' => $token]))->assertRedirect();
        }

        // Only the first three hits (the configured max) are recorded; the rest are dropped.
        $this->assertSame(3, ContactClick::count());
    }
}
