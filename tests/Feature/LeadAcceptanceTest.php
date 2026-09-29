<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\LeadAcceptStatusEnum;
use ModulesShoppingComplex\Billing\Enums\LeadRequestStatusEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\LeadAcceptanceService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Shared\Contracts\AiChatClient;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Jobs\ProcessWhatsAppWebhook;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;
use ModulesShoppingComplex\WhatsApp\Services\WhatsAppAiBotService;
use Tests\Support\RecordingWhatsAppSender;
use Tests\TestCase;

/**
 * Accept-to-charge leads (LEAD_BILLING_MODE=accept): a buyer's request costs the
 * vendor nothing until the vendor accepts it.
 */
class LeadAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER = '2348011112222';

    private const VENDOR_E164 = '2348031234567';

    private const PLATFORM = '2348000000001';

    private RecordingWhatsAppSender $whatsApp;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.leads.mode' => 'accept',
            'billing.leads.default_cost' => 10,
            'billing.leads.accept_window_hours' => 12,
            'services.whatsapp.platform_number' => self::PLATFORM,
        ]);

        Http::preventStrayRequests();

        $this->whatsApp = new RecordingWhatsAppSender;
        $this->app->instance(WhatsAppSender::class, $this->whatsApp);
    }

    private function vendor(int $coins = 100, array $attributes = []): User
    {
        $vendor = User::factory()->create(array_merge([
            'role' => 'vendor',
            'business_name' => 'Crystal Wears',
            'whatsapp_number' => '08031234567',
        ], $attributes));

        if ($coins > 0) {
            app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, $coins);
        }

        return $vendor;
    }

    private function leads(): LeadAcceptanceService
    {
        return app(LeadAcceptanceService::class);
    }

    private function balance(User $vendor): int
    {
        return app(CoinWalletService::class)->balance($vendor);
    }

    private function pendingLead(User $vendor, string $buyer = self::BUYER): BillableLead
    {
        $result = $this->leads()->request($vendor, $buyer, ViewSourceEnum::WHATSAPP);
        $this->assertSame(LeadRequestStatusEnum::REQUESTED, $result->status);
        $this->assertNotNull($result->lead);

        return $result->lead;
    }

    // ==================== Requesting ====================

    public function test_a_request_creates_a_pending_lead_without_charging(): void
    {
        $vendor = $this->vendor();

        $lead = $this->pendingLead($vendor);

        $this->assertSame(BillableLeadStateEnum::PENDING, $lead->state);
        $this->assertSame(0, $lead->coins_charged);
        $this->assertSame(self::BUYER, $lead->buyer_identity);
        $this->assertEqualsWithDelta(now()->addHours(12)->timestamp, $lead->expires_at?->timestamp, 5);
        $this->assertSame(100, $this->balance($vendor));
    }

    public function test_the_vendor_is_asked_to_accept_and_never_sees_the_buyer_number_first(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->assertCount(1, $this->whatsApp->templates);
        $template = $this->whatsApp->templates[0];

        $this->assertSame(self::VENDOR_E164, $template['to']);
        $this->assertSame('lead_request', $template['template']);
        $this->assertStringNotContainsString(self::BUYER, json_encode($template['components'], JSON_THROW_ON_ERROR));

        $payloads = array_column(array_column(array_filter($template['components'], fn ($c) => $c['type'] === 'button'), 'parameters'), 0);
        $this->assertSame(["LEAD_ACCEPT:{$lead->id}", "LEAD_DECLINE:{$lead->id}"], array_column($payloads, 'payload'));
        $this->assertSame([], $this->whatsApp->textsTo(self::BUYER), 'The buyer gets no vendor number before acceptance.');
    }

    public function test_asking_again_while_pending_does_not_open_a_second_request(): void
    {
        $vendor = $this->vendor();
        $this->pendingLead($vendor);

        $again = $this->leads()->request($vendor, self::BUYER, ViewSourceEnum::WEB);

        $this->assertSame(LeadRequestStatusEnum::ALREADY_PENDING, $again->status);
        $this->assertSame(1, BillableLead::count());
        $this->assertCount(1, $this->whatsApp->templates);
    }

    public function test_a_vendor_cannot_request_their_own_lead(): void
    {
        $vendor = $this->vendor();

        $result = $this->leads()->request($vendor, self::VENDOR_E164, ViewSourceEnum::WHATSAPP);

        $this->assertSame(LeadRequestStatusEnum::SELF, $result->status);
        $this->assertSame(0, BillableLead::count());
    }

    public function test_a_buyer_cannot_flood_vendors_with_requests(): void
    {
        config(['billing.leads.max_pending_per_buyer' => 2]);

        $this->pendingLead($this->vendor(attributes: ['whatsapp_number' => '08031111111']));
        $this->pendingLead($this->vendor(attributes: ['whatsapp_number' => '08032222222']));

        $third = $this->leads()->request($this->vendor(attributes: ['whatsapp_number' => '08033333333']), self::BUYER, ViewSourceEnum::WHATSAPP);

        $this->assertSame(LeadRequestStatusEnum::TOO_MANY_PENDING, $third->status);
        $this->assertSame(2, BillableLead::count());
    }

    // ==================== Accepting ====================

    public function test_accepting_charges_the_vendor_and_sends_the_buyer_the_vendor_contact(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $result = $this->leads()->accept($lead);

        $this->assertSame(LeadAcceptStatusEnum::ACCEPTED, $result->status);
        $this->assertSame(90, $this->balance($vendor));

        $lead->refresh();
        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead->state);
        $this->assertSame(10, $lead->coins_charged);
        $this->assertNotNull($lead->accepted_at);

        $buyerMessages = $this->whatsApp->textsTo(self::BUYER);
        $this->assertCount(1, $buyerMessages);
        $this->assertStringContainsString('https://wa.me/'.self::VENDOR_E164, $buyerMessages[0]);

        $this->assertSame(
            ['phone' => '+'.self::BUYER, 'whatsapp_url' => 'https://wa.me/'.self::BUYER],
            $this->leads()->buyerContactFor($lead),
        );
    }

    public function test_the_accepted_lead_does_not_also_trigger_the_click_mode_alert(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->leads()->accept($lead);

        $this->assertSame(['lead_request'], array_column($this->whatsApp->templates, 'template'));
    }

    public function test_accepting_twice_charges_once(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->leads()->accept($lead);
        $second = $this->leads()->accept($lead);

        $this->assertSame(LeadAcceptStatusEnum::ALREADY_ACCEPTED, $second->status);
        $this->assertSame(90, $this->balance($vendor));
    }

    public function test_accepting_without_enough_coins_keeps_the_request_open(): void
    {
        $vendor = $this->vendor(coins: 5);
        $lead = $this->pendingLead($vendor);

        $result = $this->leads()->accept($lead);

        $this->assertSame(LeadAcceptStatusEnum::INSUFFICIENT_BALANCE, $result->status);
        $this->assertSame(BillableLeadStateEnum::PENDING, $lead->refresh()->state);
        $this->assertSame(5, $this->balance($vendor));
        $this->assertSame([], $this->whatsApp->textsTo(self::BUYER));
    }

    public function test_the_vendor_daily_cap_blocks_acceptance(): void
    {
        $vendor = $this->vendor(attributes: ['daily_coin_cap' => 5]);
        $lead = $this->pendingLead($vendor);

        $result = $this->leads()->accept($lead);

        $this->assertSame(LeadAcceptStatusEnum::DAILY_CAP, $result->status);
        $this->assertSame(100, $this->balance($vendor));
    }

    public function test_a_buyer_already_accepted_gets_the_contact_again_for_free(): void
    {
        $vendor = $this->vendor();
        $this->leads()->accept($this->pendingLead($vendor));

        $again = $this->leads()->request($vendor, self::BUYER, ViewSourceEnum::WEB);

        $this->assertSame(LeadRequestStatusEnum::ALREADY_ACCEPTED, $again->status);
        $this->assertSame(1, $again->lead?->repeat_count);
        $this->assertSame(90, $this->balance($vendor));
        $this->assertSame(1, BillableLead::count());
    }

    // ==================== Declining and expiry ====================

    public function test_declining_charges_nothing_and_tells_the_buyer(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->assertTrue($this->leads()->decline($lead));

        $this->assertSame(BillableLeadStateEnum::DECLINED, $lead->refresh()->state);
        $this->assertSame(100, $this->balance($vendor));
        $this->assertStringContainsString("can't take your request", $this->whatsApp->textsTo(self::BUYER)[0]);
        $this->assertStringNotContainsString(self::VENDOR_E164, implode(' ', $this->whatsApp->textsTo(self::BUYER)));
    }

    public function test_a_declined_buyer_cannot_request_the_same_vendor_again_inside_the_window(): void
    {
        $vendor = $this->vendor();
        $this->leads()->decline($this->pendingLead($vendor));

        $again = $this->leads()->request($vendor, self::BUYER, ViewSourceEnum::WHATSAPP);

        $this->assertSame(LeadRequestStatusEnum::DECLINED, $again->status);
        $this->assertSame(1, BillableLead::count());
    }

    public function test_an_unanswered_request_expires_unbilled_and_the_buyer_is_told(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->travel(13)->hours();

        $this->assertSame(1, $this->leads()->expireOverdue());
        $this->assertSame(BillableLeadStateEnum::EXPIRED, $lead->refresh()->state);
        $this->assertStringContainsString("hasn't responded", $this->whatsApp->textsTo(self::BUYER)[0]);

        $this->assertSame(LeadAcceptStatusEnum::CLOSED, $this->leads()->accept($lead)->status);
        $this->assertSame(100, $this->balance($vendor));
    }

    public function test_an_overdue_request_cannot_be_accepted_even_before_the_sweep_runs(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->travel(13)->hours();

        $this->assertSame(LeadAcceptStatusEnum::CLOSED, $this->leads()->accept($lead)->status);
        $this->assertSame(BillableLeadStateEnum::EXPIRED, $lead->refresh()->state);
        $this->assertSame(100, $this->balance($vendor));
    }

    public function test_after_expiry_the_buyer_may_ask_again(): void
    {
        $vendor = $this->vendor();
        $this->pendingLead($vendor);
        $this->travel(13)->hours();

        $again = $this->leads()->request($vendor, self::BUYER, ViewSourceEnum::WHATSAPP);

        $this->assertSame(LeadRequestStatusEnum::REQUESTED, $again->status);
        $this->assertSame(2, BillableLead::count());
    }

    // ==================== WhatsApp buttons ====================

    private function buttonTap(string $from, string $payload): void
    {
        (new ProcessWhatsAppWebhook([
            'entry' => [['changes' => [['value' => ['messages' => [[
                'from' => $from,
                'id' => 'wamid.'.uniqid(),
                'type' => 'button',
                'button' => ['payload' => $payload, 'text' => 'Accept'],
            ]]]]]]],
        ]))->handle(app(WhatsAppAiBotService::class), $this->leads());
    }

    public function test_the_vendor_accept_button_charges_and_replies_with_the_buyer_contact(): void
    {
        $this->app->instance(AiChatClient::class, new FailingAiChatClient);
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->buttonTap(self::VENDOR_E164, "LEAD_ACCEPT:{$lead->id}");

        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead->refresh()->state);
        $vendorReply = $this->whatsApp->textsTo(self::VENDOR_E164);
        $this->assertCount(1, $vendorReply);
        $this->assertStringContainsString('https://wa.me/'.self::BUYER, $vendorReply[0]);
    }

    public function test_a_button_from_another_number_cannot_answer_for_the_vendor(): void
    {
        $this->app->instance(AiChatClient::class, new FailingAiChatClient);
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->buttonTap('2348099998888', "LEAD_ACCEPT:{$lead->id}");

        $this->assertSame(BillableLeadStateEnum::PENDING, $lead->refresh()->state);
        $this->assertSame(100, $this->balance($vendor));
    }

    // ==================== Web buttons and the bot ====================

    public function test_the_web_button_opens_the_platform_chat_with_a_reference_and_bills_nothing(): void
    {
        $vendor = $this->vendor();

        $response = $this->get('/contact/'.$vendor->slug);

        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/'.self::PLATFORM.'?text=', $location);
        $this->assertStringNotContainsString(self::VENDOR_E164, $location);
        $this->assertNotNull(LeadAcceptanceService::referenceIn(rawurldecode($location)));
        $this->assertSame(0, ContactClick::count());
        $this->assertSame(0, BillableLead::count());
    }

    public function test_the_bot_turns_a_reference_message_into_a_request_without_the_ai(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        $this->app->instance(AiChatClient::class, new FailingAiChatClient);
        $vendor = $this->vendor();

        $location = (string) $this->get('/contact/'.$vendor->slug)->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        (new ProcessWhatsAppWebhook([
            'entry' => [['changes' => [['value' => ['messages' => [[
                'from' => self::BUYER,
                'id' => 'wamid.ref',
                'type' => 'text',
                'text' => ['body' => (string) $query['text']],
            ]]]]]]],
        ]))->handle(app(WhatsAppAiBotService::class), $this->leads());

        $lead = BillableLead::sole();
        $this->assertSame(BillableLeadStateEnum::PENDING, $lead->state);
        $this->assertSame(self::BUYER, $lead->buyer_identity);
        $this->assertSame($vendor->id, $lead->vendor_id);
        $this->assertSame(ViewSourceEnum::WEB, $lead->channel);
        Queue::assertPushed(SendWhatsAppMessage::class);
    }

    // ==================== Leads page ====================

    public function test_the_vendor_can_accept_from_the_leads_page_and_then_sees_the_buyer(): void
    {
        $vendor = $this->vendor();
        $lead = $this->pendingLead($vendor);

        $this->actingAs($vendor)->get('/vendor/leads')->assertInertia(fn (Assert $page) => $page
            ->where('leads.data.0.state', 'pending')
            ->where('leads.data.0.can_respond', true)
            ->where('leads.data.0.buyer_contact', null));

        $this->actingAs($vendor)->post("/vendor/leads/{$lead->id}/accept")->assertRedirect();

        $this->assertSame(90, $this->balance($vendor));
        $this->actingAs($vendor)->get('/vendor/leads')->assertInertia(fn (Assert $page) => $page
            ->where('leads.data.0.state', 'billed')
            ->where('leads.data.0.buyer_contact.phone', '+'.self::BUYER));
    }

    public function test_a_vendor_cannot_answer_another_vendors_lead(): void
    {
        $lead = $this->pendingLead($this->vendor());
        $intruder = $this->vendor(attributes: ['whatsapp_number' => '08039999999']);

        $this->actingAs($intruder)->post("/vendor/leads/{$lead->id}/accept")->assertNotFound();
        $this->actingAs($intruder)->post("/vendor/leads/{$lead->id}/decline")->assertNotFound();

        $this->assertSame(BillableLeadStateEnum::PENDING, $lead->refresh()->state);
    }
}

final class FailingAiChatClient implements AiChatClient
{
    public function createMessage(array $payload): array
    {
        throw new \LogicException('The AI must not be called for this message.');
    }
}
