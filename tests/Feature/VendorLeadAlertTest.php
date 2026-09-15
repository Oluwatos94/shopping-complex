<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Events\VendorLeadCharged;
use ModulesShoppingComplex\Billing\Listeners\SendVendorLeadAlert;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Billing\Services\LeadBillingService;
use ModulesShoppingComplex\Discovery\Services\GeoLocationService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Jobs\SendNotificationEmailJob;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppInteractionEventEnum;
use ModulesShoppingComplex\WhatsApp\Models\WhatsAppInteraction;
use Tests\TestCase;

class VendorLeadAlertTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER = '2348011112222';

    private SpyWhatsAppSender $whatsApp;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.leads.default_cost' => 10,
            'billing.coins.expiry_months' => 12,
            'services.google_maps.key' => 'test-geo-key',
        ]);

        // Silence the automatic (sync) listener; each test drives it explicitly.
        Event::fake([VendorLeadCharged::class]);

        $this->whatsApp = new SpyWhatsAppSender;
        $this->app->instance(WhatsAppSender::class, $this->whatsApp);

        // GeoLocationService is final; stub its one HTTP call instead of mocking it.
        Http::fake(['maps.googleapis.com/*' => Http::response([
            'results' => [[
                'address_components' => [
                    ['long_name' => 'Yaba', 'short_name' => 'Yaba', 'types' => ['locality']],
                    ['long_name' => 'Lagos', 'short_name' => 'LA', 'types' => ['administrative_area_level_1']],
                ],
                'formatted_address' => 'Yaba, Lagos, Nigeria',
            ]],
        ])]);
    }

    private function vendor(?string $whatsapp = '08031234567'): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Crystal Wears',
            'whatsapp_number' => $whatsapp,
        ]);
    }

    private function funded(User $vendor, int $coins = 100): User
    {
        app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, $coins);

        return $vendor;
    }

    private function loggedSearch(string $phone, string $query): void
    {
        WhatsAppInteraction::create([
            'phone_number' => $phone,
            'event_type' => WhatsAppInteractionEventEnum::SEARCH,
            'search_query' => $query,
            'buyer_latitude' => 6.5,
            'buyer_longitude' => 3.37,
        ]);
    }

    private function chargeALead(User $vendor): BillableLead
    {
        $links = app(ContactLinkService::class);
        $click = $links->recordClick($links->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER));

        return app(LeadBillingService::class)->bill($click);
    }

    // ==================== Trigger ====================

    public function test_a_billed_lead_fires_exactly_one_alert_event(): void
    {
        Event::fake([VendorLeadCharged::class]);
        $vendor = $this->funded($this->vendor());

        $this->chargeALead($vendor);

        Event::assertDispatchedTimes(VendorLeadCharged::class, 1);
    }

    public function test_a_repeat_lead_fires_no_alert(): void
    {
        Event::fake([VendorLeadCharged::class]);
        $vendor = $this->funded($this->vendor());
        $links = app(ContactLinkService::class);

        // Two clicks in the same window: the second is a repeat.
        app(LeadBillingService::class)->bill($links->recordClick($links->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER)));
        app(LeadBillingService::class)->bill($links->recordClick($links->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER)));

        Event::assertDispatchedTimes(VendorLeadCharged::class, 1);
    }

    public function test_an_unbilled_lead_fires_no_alert(): void
    {
        Event::fake([VendorLeadCharged::class]);
        $vendor = $this->vendor(); // no coins credited → nothing charged

        $this->chargeALead($vendor);

        Event::assertNotDispatched(VendorLeadCharged::class);
    }

    // ==================== WhatsApp alert ====================

    public function test_the_alert_sends_a_whatsapp_template_with_the_lead_details(): void
    {
        $vendor = $this->funded($this->vendor());
        $this->loggedSearch(self::BUYER, 'ankara gown');
        $lead = $this->chargeALead($vendor);

        app(SendVendorLeadAlert::class)->handle(new VendorLeadCharged($lead));

        $this->assertCount(1, $this->whatsApp->templates);
        $sent = $this->whatsApp->templates[0];
        $this->assertSame('2348031234567', $sent['to']);
        $this->assertSame('lead_alert', $sent['template']);

        $params = array_column($sent['components'][0]['parameters'], 'text');
        $this->assertSame('Crystal Wears', $params[0]);
        $this->assertSame('ankara gown', $params[1]);
        $this->assertSame('Yaba, Lagos', $params[2]);
        $this->assertSame('10', $params[3]);   // coins charged
        $this->assertSame('90', $params[4]);   // remaining balance
    }

    public function test_the_alert_always_records_an_in_app_notification(): void
    {
        $vendor = $this->funded($this->vendor());
        $lead = $this->chargeALead($vendor);

        app(SendVendorLeadAlert::class)->handle(new VendorLeadCharged($lead));

        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'lead_alert']);
    }

    public function test_a_buyer_with_no_logged_search_uses_neutral_wording(): void
    {
        $vendor = $this->funded($this->vendor());
        $lead = $this->chargeALead($vendor);

        app(SendVendorLeadAlert::class)->handle(new VendorLeadCharged($lead));

        $params = array_column($this->whatsApp->templates[0]['components'][0]['parameters'], 'text');
        $this->assertSame('a product or service', $params[1]);
        $this->assertSame('your area', $params[2]);
    }

    // ==================== Email fallback ====================

    public function test_a_vendor_with_no_usable_number_gets_an_email_fallback(): void
    {
        Queue::fake([SendNotificationEmailJob::class]);
        $vendor = $this->funded($this->vendor());
        $lead = $this->chargeALead($vendor);

        // The number became unusable between the contact and the alert.
        $vendor->forceFill(['whatsapp_number' => null])->save();

        app(SendVendorLeadAlert::class)->handle(new VendorLeadCharged($lead));

        $this->assertCount(0, $this->whatsApp->templates);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'lead_alert']);
        Queue::assertPushed(SendNotificationEmailJob::class);
    }

    // ==================== Idempotency & isolation ====================

    public function test_a_retry_does_not_add_a_second_in_app_notification(): void
    {
        $vendor = $this->funded($this->vendor());
        $lead = $this->chargeALead($vendor);

        app(SendVendorLeadAlert::class)->handle(new VendorLeadCharged($lead));
        app(SendVendorLeadAlert::class)->handle(new VendorLeadCharged($lead));

        $this->assertSame(1, Notification::where('type', 'lead_alert')->count());
    }

    public function test_a_template_failure_never_undoes_the_charge(): void
    {
        $vendor = $this->funded($this->vendor(), 100);
        $lead = $this->chargeALead($vendor);

        $this->whatsApp->throwOnTemplate = true;

        try {
            app(SendVendorLeadAlert::class)->handle(new VendorLeadCharged($lead));
            $this->fail('expected the template failure to propagate for retry');
        } catch (\RuntimeException) {
        }

        // The debit stands and the in-app receipt was still recorded.
        $this->assertSame(90, app(CoinWalletService::class)->balance($vendor));
        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead->fresh()->state);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'lead_alert']);
    }
}

class SpyWhatsAppSender implements WhatsAppSender
{
    /** @var array<int, array{to: string, template: string, components: array}> */
    public array $templates = [];

    public bool $throwOnTemplate = false;

    public function sendText(string $to, string $body): void {}

    public function sendTemplate(string $to, string $templateName, string $lang, array $components = []): void
    {
        if ($this->throwOnTemplate) {
            throw new \RuntimeException('template send failed');
        }

        $this->templates[] = ['to' => $to, 'template' => $templateName, 'components' => $components];
    }
}
