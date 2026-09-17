<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Auth\Events\Login;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Models\ContactLink;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use Tests\Support\NullWhatsAppSender;
use Tests\TestCase;

class VisitorIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.leads.default_cost' => 10, 'billing.coins.expiry_months' => 12]);
        $this->app->instance(WhatsAppSender::class, new NullWhatsAppSender);
    }

    private function vendor(): User
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'whatsapp_number' => '08031234567']);
        app(CoinWalletService::class)->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 1000);

        return $vendor;
    }

    private function webLink(User $vendor): string
    {
        return app(ContactLinkService::class)->mint($vendor, ViewSourceEnum::WEB)->token;
    }

    private function links(): ContactLinkService
    {
        return app(ContactLinkService::class);
    }

    // ==================== Cookie ====================

    public function test_the_first_page_view_sets_a_visitor_cookie(): void
    {
        $this->get('/')->assertCookie(ContactLinkService::VISITOR_COOKIE);
    }

    // ==================== Resolver ====================

    public function test_the_resolver_prefers_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->assertSame('user_'.$user->id, $this->actingAs($user)->links()->resolveBuyerIdentity(Request::create('/', 'GET')));
    }

    public function test_the_resolver_uses_the_visitor_cookie_when_anonymous(): void
    {
        $request = Request::create('/', 'GET');
        $request->cookies->set(ContactLinkService::VISITOR_COOKIE, 'abc-123');

        $this->assertSame('visitor_abc-123', $this->links()->resolveBuyerIdentity($request));
    }

    public function test_the_resolver_falls_back_to_an_ip_user_agent_hash(): void
    {
        $identity = $this->links()->resolveBuyerIdentity(Request::create('/', 'GET'));

        $this->assertStringStartsWith('anon_', $identity);
    }

    // ==================== Redirect attribution & dedupe ====================

    public function test_a_web_redirect_attributes_and_bills_the_visitor(): void
    {
        $vendor = $this->vendor();
        $token = $this->webLink($vendor);

        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie(ContactLinkService::VISITOR_COOKIE, 'browser-1')
            ->get(route('contact.redirect', ['token' => $token]))
            ->assertRedirect();

        $lead = BillableLead::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame('visitor_browser-1', $lead->buyer_identity);
        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead->state);
    }

    public function test_the_first_contact_redirect_attributes_the_new_visitor(): void
    {
        $vendor = $this->vendor();

        $this->get(route('contact.redirect', ['token' => $this->webLink($vendor)]))->assertRedirect();

        $lead = BillableLead::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertStringStartsWith('visitor_', $lead->buyer_identity);
    }

    public function test_the_same_browser_deduplicates_across_hits(): void
    {
        $vendor = $this->vendor();
        $token = $this->webLink($vendor);

        $this->withoutMiddleware(EncryptCookies::class);
        $this->withUnencryptedCookie(ContactLinkService::VISITOR_COOKIE, 'browser-1')
            ->get(route('contact.redirect', ['token' => $token]));
        $this->withUnencryptedCookie(ContactLinkService::VISITOR_COOKIE, 'browser-1')
            ->get(route('contact.redirect', ['token' => $token]));

        $lead = BillableLead::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame('visitor_browser-1', $lead->buyer_identity);
        $this->assertSame(1, BillableLead::where('vendor_id', $vendor->id)->count());
    }

    public function test_reaching_a_vendor_never_requires_login(): void
    {
        $vendor = $this->vendor();

        $this->assertGuest();
        $this->get(route('contact.redirect', ['token' => $this->webLink($vendor)]))->assertRedirect();
    }

    // ==================== Web contact button -> signed redirect ====================

    public function test_the_web_contact_route_issues_a_signed_redirect_with_the_message(): void
    {
        $vendor = $this->vendor();

        $response = $this->get('/contact/'.$vendor->slug.'?message='.urlencode('Hi there'));

        $response->assertRedirect();
        $this->assertStringContainsString('/c/', (string) $response->headers->get('Location'));

        $link = ContactLink::firstOrFail();
        $this->assertSame($vendor->id, $link->vendor_id);
        $this->assertSame(ViewSourceEnum::WEB, $link->source);
        $this->assertSame('Hi there', $link->prefilled_message);
        $this->assertNull($link->buyer_identity);
    }

    public function test_a_vendor_without_a_number_has_no_web_contact_route(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'whatsapp_number' => null]);

        $this->get('/contact/'.$vendor->slug)->assertNotFound();
    }

    public function test_the_web_button_records_a_billable_lead_attributed_to_the_visitor(): void
    {
        $vendor = $this->vendor();

        $target = (string) $this->get('/contact/'.$vendor->slug)->headers->get('Location');
        $path = parse_url($target, PHP_URL_PATH).'?'.(parse_url($target, PHP_URL_QUERY) ?? '');

        $this->withoutMiddleware(EncryptCookies::class)
            ->withUnencryptedCookie(ContactLinkService::VISITOR_COOKIE, 'browser-web')
            ->get($path)
            ->assertRedirect();

        $lead = BillableLead::where('vendor_id', $vendor->id)->firstOrFail();
        $this->assertSame('visitor_browser-web', $lead->buyer_identity);
        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead->state);
    }

    // ==================== Merge on login ====================

    public function test_logging_in_merges_the_visitor_history_into_the_account(): void
    {
        $vendor = $this->vendor();
        $user = User::factory()->create();

        $lead = BillableLead::create([
            'vendor_id' => $vendor->id,
            'buyer_identity' => 'visitor_browser-x',
            'channel' => ViewSourceEnum::WEB,
            'coins_charged' => 10,
            'state' => BillableLeadStateEnum::CHARGED,
            'window_start' => now(),
        ]);
        $click = ContactClick::create([
            'contact_link_id' => $this->links()->mint($vendor, ViewSourceEnum::WEB)->id,
            'vendor_id' => $vendor->id,
            'source' => ViewSourceEnum::WEB,
            'buyer_identity' => 'visitor_browser-x',
            'is_billable' => true,
            'created_at' => now(),
        ]);

        $request = Request::create('/login', 'POST');
        $request->cookies->set(ContactLinkService::VISITOR_COOKIE, 'browser-x');
        $this->app->instance('request', $request);

        event(new Login('web', $user, false));

        $this->assertSame('user_'.$user->id, $lead->fresh()->buyer_identity);
        $this->assertSame('user_'.$user->id, $click->fresh()->buyer_identity);
    }
}
