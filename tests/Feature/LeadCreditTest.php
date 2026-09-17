<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\LeadCreditReasonEnum;
use ModulesShoppingComplex\Billing\Jobs\CreditFailedLeads;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Billing\Services\LeadCreditService;
use ModulesShoppingComplex\Billing\Services\LeadFailureDetector;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Support\WhatsAppPhone;
use Tests\TestCase;

class LeadCreditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.coins.expiry_months' => 12,
            'billing.guards.velocity_buyer.vendors' => 4,
            'billing.guards.velocity_buyer.minutes' => 5,
            'billing.guards.velocity_ip.identities' => 3,
            'billing.guards.velocity_ip.minutes' => 10,
            'billing.credits.lookback_days' => 35,
            'billing.credits.alert_rate' => 0.05,
        ]);
    }

    private function vendor(string $whatsapp = '08031234567'): User
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'whatsapp_number' => $whatsapp]);
        app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 1000);

        return $vendor;
    }

    private function chargeLead(User $vendor, string $buyer, int $coins = 10, ?ContactClick $click = null, ?Carbon $windowStart = null): BillableLead
    {
        $lead = BillableLead::create([
            'contact_click_id' => $click?->id,
            'vendor_id' => $vendor->id,
            'buyer_identity' => $buyer,
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => $coins,
            'state' => BillableLeadStateEnum::CHARGED,
            'delivered_number' => WhatsAppPhone::toE164((string) $vendor->whatsapp_number),
            'window_start' => $windowStart ?? now(),
            'repeat_count' => 0,
            'last_click_at' => now(),
        ]);

        app(CoinWalletService::class)->debit($vendor, $coins, $lead);

        return $lead;
    }

    private function click(User $vendor, string $buyer, ?string $ip = '10.0.0.1'): ContactClick
    {
        $link = app(ContactLinkService::class)->mint($vendor, ViewSourceEnum::WHATSAPP, $buyer);

        return ContactClick::create([
            'contact_link_id' => $link->id,
            'vendor_id' => $vendor->id,
            'source' => ViewSourceEnum::WHATSAPP,
            'buyer_identity' => $buyer,
            'is_billable' => true,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }

    private function detector(): LeadFailureDetector
    {
        return app(LeadFailureDetector::class);
    }

    private function credits(): LeadCreditService
    {
        return app(LeadCreditService::class);
    }

    // ==================== Detection ====================

    public function test_a_duplicate_inside_the_window_is_credited(): void
    {
        $vendor = $this->vendor();

        $first = $this->chargeLead($vendor, '2348011112222', windowStart: now()->subDays(2));
        $second = $this->chargeLead($vendor, '2348011112222', windowStart: now());

        $this->assertSame(LeadCreditReasonEnum::DUPLICATE, $this->detector()->reasonFor($second));
        $this->assertNull($this->detector()->reasonFor($first));

        $this->credits()->credit($second, LeadCreditReasonEnum::DUPLICATE);

        $this->assertSame(LeadCreditReasonEnum::DUPLICATE, $second->fresh()->credit_reason);
        $this->assertNull($first->fresh()->credit_reason);
        $this->assertSame(990, app(CoinWalletService::class)->balance($vendor)); // one of the two charges returned
    }

    public function test_an_unreachable_number_is_credited(): void
    {
        $vendor = $this->vendor('not-a-number');

        $lead = $this->chargeLead($vendor, '2348011112222');

        $this->assertSame(LeadCreditReasonEnum::INVALID_NUMBER, $this->detector()->reasonFor($lead));
    }

    public function test_the_number_check_uses_the_delivered_value_not_the_current_one(): void
    {
        $vendor = $this->vendor('08031234567');
        $lead = $this->chargeLead($vendor, '2348011112222');

        $vendor->forceFill(['whatsapp_number' => 'broken-later'])->save();

        $this->assertNull($this->detector()->reasonFor($lead->fresh()));
    }

    public function test_a_flagged_burst_is_credited(): void
    {
        $vendor = $this->vendor();
        $click = $this->click($vendor, '2348010000001', '203.0.113.9');
        $this->click($vendor, '2348010000002', '203.0.113.9');
        $this->click($vendor, '2348010000003', '203.0.113.9'); // one IP, three identities

        $lead = $this->chargeLead($vendor, '2348010000001', click: $click);

        $this->assertSame(LeadCreditReasonEnum::FLAGGED, $this->detector()->reasonFor($lead));
    }

    public function test_a_cleanly_delivered_lead_is_not_credited(): void
    {
        $vendor = $this->vendor();
        $click = $this->click($vendor, '2348011112222', '10.0.0.5');

        $lead = $this->chargeLead($vendor, '2348011112222', click: $click);

        $this->assertNull($this->detector()->reasonFor($lead));
    }

    // ==================== Idempotency & the job ====================

    public function test_the_job_credits_failed_leads_once(): void
    {
        $vendor = $this->vendor('not-a-number');
        $lead = $this->chargeLead($vendor, '2348011112222');

        (new CreditFailedLeads)->handle($this->detector(), $this->credits());
        (new CreditFailedLeads)->handle($this->detector(), $this->credits());

        $this->assertSame(LeadCreditReasonEnum::INVALID_NUMBER, $lead->fresh()->credit_reason);
        $this->assertSame(1000, app(CoinWalletService::class)->balance($vendor)); // credited exactly once
    }

    public function test_a_clean_lead_survives_the_job_uncredited(): void
    {
        $vendor = $this->vendor();
        $lead = $this->chargeLead($vendor, '2348011112222', click: $this->click($vendor, '2348011112222', '10.0.0.9'));

        (new CreditFailedLeads)->handle($this->detector(), $this->credits());

        $this->assertNull($lead->fresh()->credit_reason);
        $this->assertSame(990, app(CoinWalletService::class)->balance($vendor));
    }

    // ==================== Health metric ====================

    public function test_the_credit_rate_reports_credited_over_billed(): void
    {
        $vendor = $this->vendor();
        $this->chargeLead($vendor, '2348010000001');
        $this->chargeLead($vendor, '2348010000002');
        $this->chargeLead($vendor, '2348010000003');
        $bad = $this->chargeLead($vendor, '2348010000004');

        $this->credits()->credit($bad, LeadCreditReasonEnum::INVALID_NUMBER);

        $rate = $this->credits()->creditRate(now()->subDay(), now()->addDay());

        $this->assertEqualsWithDelta(0.25, $rate, 0.0001);
    }
}
