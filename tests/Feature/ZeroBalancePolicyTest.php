<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Events\VendorLeadMissed;
use ModulesShoppingComplex\Billing\Listeners\SendMissedLeadAlert;
use ModulesShoppingComplex\Billing\Listeners\WarnLowCoinBalance;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Billing\Services\LeadBillingService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Jobs\SendNotificationEmailJob;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use Tests\Support\NullWhatsAppSender;
use Tests\TestCase;

class ZeroBalancePolicyTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER = '2348011112222';

    protected function setUp(): void
    {
        parent::setUp();

        // Standard lead costs 10; the low-balance threshold is 3 leads = 30 coins.
        config([
            'billing.leads.default_cost' => 10,
            'billing.leads.low_balance_leads' => 3,
            'billing.coins.expiry_months' => 12,
            'services.google_maps.key' => 'test-geo-key',
        ]);

        Http::fake(['maps.googleapis.com/*' => Http::response(['results' => []])]);

        // A charged lead fires the WhatsApp receipt; keep it off the network.
        $this->app->instance(WhatsAppSender::class, new NullWhatsAppSender);
    }

    private function vendor(?string $whatsapp = '08031234567'): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Crystal Wears',
            'whatsapp_number' => $whatsapp,
        ]);
    }

    private function funded(User $vendor, int $coins): User
    {
        app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, $coins);

        return $vendor;
    }

    private function billLead(User $vendor, string $buyer = self::BUYER): ?BillableLead
    {
        $links = app(ContactLinkService::class);
        $click = $links->recordClick($links->mint($vendor, ViewSourceEnum::WHATSAPP, $buyer));

        return app(LeadBillingService::class)->bill($click);
    }

    // ==================== Buyer is never degraded ====================

    public function test_a_zero_balance_vendor_still_delivers_the_contact_link(): void
    {
        Event::fake([VendorLeadMissed::class, VendorLeadCharged::class]);
        $vendor = $this->vendor();
        $links = app(ContactLinkService::class);

        $url = (string) $links->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);
        $this->get($url)->assertRedirect();

        $lead = BillableLead::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame(BillableLeadStateEnum::UNBILLED, $lead->state);
        $this->assertSame(0, $lead->coins_charged);
    }

    // ==================== The lead is recorded, not billed ====================

    public function test_an_insufficient_balance_records_an_unbilled_lead(): void
    {
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->vendor();

        $lead = $this->billLead($vendor);

        $this->assertSame(BillableLeadStateEnum::UNBILLED, $lead?->state);
        $this->assertSame(0, $lead?->coins_charged);
    }

    public function test_an_unbilled_lead_cannot_be_charged_retroactively(): void
    {
        Event::fake([VendorLeadMissed::class, VendorLeadCharged::class]);
        $vendor = $this->vendor();

        // Missed while broke, then the vendor tops up and the buyer returns in-window.
        $this->billLead($vendor);
        $this->funded($vendor, 100);
        $second = $this->billLead($vendor);

        $this->assertSame(1, BillableLead::count());
        $this->assertSame(BillableLeadStateEnum::UNBILLED, $second?->state);
        $this->assertSame(0, $second?->coins_charged);
        $this->assertSame(100, app(CoinWalletService::class)->balance($vendor));
    }

    // ==================== Missed-lead alert ====================

    public function test_a_missed_lead_fires_exactly_one_alert_event(): void
    {
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->vendor();

        $this->billLead($vendor);

        Event::assertDispatchedTimes(VendorLeadMissed::class, 1);
    }

    public function test_a_repeat_missed_lead_fires_no_second_alert(): void
    {
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->vendor();

        $this->billLead($vendor);
        $this->billLead($vendor);

        Event::assertDispatchedTimes(VendorLeadMissed::class, 1);
    }

    public function test_a_charged_lead_fires_no_missed_alert(): void
    {
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->funded($this->vendor(), 100);

        $this->billLead($vendor);

        Event::assertNotDispatched(VendorLeadMissed::class);
    }

    public function test_the_missed_alert_records_an_in_app_notification_with_a_top_up_link(): void
    {
        Queue::fake([SendNotificationEmailJob::class]);
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->vendor();
        $lead = $this->billLead($vendor);

        app(SendMissedLeadAlert::class)->handle(new VendorLeadMissed($lead, 10));

        $notification = Notification::where('user_id', $vendor->id)->where('type', 'lead_missed')->firstOrFail();
        $this->assertSame('top_up', $notification->data['action']);
        $this->assertSame(10, $notification->data['missed_cost']);
        $this->assertSame($lead->id, $notification->data['lead_id']);
        Queue::assertPushed(SendNotificationEmailJob::class);
    }

    public function test_the_missed_alert_is_idempotent_on_retry(): void
    {
        Queue::fake([SendNotificationEmailJob::class]);
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->vendor();
        $lead = $this->billLead($vendor);

        app(SendMissedLeadAlert::class)->handle(new VendorLeadMissed($lead, 10));
        app(SendMissedLeadAlert::class)->handle(new VendorLeadMissed($lead, 10));

        $this->assertSame(1, Notification::where('type', 'lead_missed')->count());
    }

    public function test_a_burst_of_missed_leads_collapses_into_one_notification_and_one_email(): void
    {
        Queue::fake([SendNotificationEmailJob::class]);
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->vendor();

        foreach (['2348011110001', '2348011110002', '2348011110003'] as $buyer) {
            app(SendMissedLeadAlert::class)->handle(new VendorLeadMissed($this->billLead($vendor, $buyer), 10));
        }

        $notification = Notification::where('user_id', $vendor->id)->where('type', 'lead_missed')->sole();
        $this->assertSame(3, $notification->group_count);
        Queue::assertPushed(SendNotificationEmailJob::class, 1);
    }

    public function test_the_missed_alert_uses_the_cost_from_the_billing_attempt(): void
    {
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->vendor();

        $this->billLead($vendor);

        Event::assertDispatched(VendorLeadMissed::class, fn (VendorLeadMissed $e) => $e->attemptedCost === 10);
    }

    public function test_the_missed_message_describes_insufficient_not_zero_balance(): void
    {
        Queue::fake([SendNotificationEmailJob::class]);
        Event::fake([VendorLeadMissed::class]);
        $vendor = $this->funded($this->vendor(), 3); // 3 coins, lead costs 10: has coins, just not enough
        $lead = $this->billLead($vendor);

        app(SendMissedLeadAlert::class)->handle(new VendorLeadMissed($lead, 10));

        $message = Notification::where('type', 'lead_missed')->firstOrFail()->message;
        $this->assertStringContainsString('did not have enough coins', $message);
    }

    // ==================== Low-balance warning ====================

    public function test_a_charge_that_drops_below_the_threshold_warns_the_vendor(): void
    {
        Event::fake([VendorLeadCharged::class]);
        $vendor = $this->funded($this->vendor(), 35); // 35 - 10 = 25, below the 30 threshold
        $lead = $this->billLead($vendor);

        app(WarnLowCoinBalance::class)->handle(new VendorLeadCharged($lead));

        $notification = Notification::where('user_id', $vendor->id)->where('type', 'low_balance')->firstOrFail();
        $this->assertSame(25, $notification->data['balance']);
        $this->assertSame(30, $notification->data['threshold']);
    }

    public function test_a_balance_above_the_threshold_is_not_warned(): void
    {
        Event::fake([VendorLeadCharged::class]);
        $vendor = $this->funded($this->vendor(), 100); // 100 - 10 = 90, well above threshold
        $lead = $this->billLead($vendor);

        app(WarnLowCoinBalance::class)->handle(new VendorLeadCharged($lead));

        $this->assertDatabaseMissing('notifications', ['user_id' => $vendor->id, 'type' => 'low_balance']);
    }

    public function test_the_low_balance_warning_is_not_repeated_while_unread(): void
    {
        Event::fake([VendorLeadCharged::class]);
        $vendor = $this->funded($this->vendor(), 35);

        $first = $this->billLead($vendor);
        app(WarnLowCoinBalance::class)->handle(new VendorLeadCharged($first));

        $second = $this->billLead($vendor, '2348099998888');
        app(WarnLowCoinBalance::class)->handle(new VendorLeadCharged($second));

        $this->assertSame(1, Notification::where('type', 'low_balance')->count());
    }

    // ==================== Dashboard surfacing ====================

    public function test_the_dashboard_surfaces_the_missed_leads_and_low_balance(): void
    {
        $vendor = $this->vendor();
        Event::fake([VendorLeadMissed::class]);
        $this->billLead($vendor);
        $this->billLead($vendor, '2348099998888');

        $this->actingAs($vendor)->get('/vendor')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Vendor/Dashboard', false)
                ->where('coins.missed_leads', 2)
                ->where('coins.balance', 0)
                ->where('coins.low_balance', true)
                ->where('coins.low_balance_threshold', 30)
        );
    }
}
