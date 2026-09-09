<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Models\ContactLink;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Enums\WhatsAppSessionStateEnum;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;
use ModulesShoppingComplex\WhatsApp\Models\WhatsAppSession;
use ModulesShoppingComplex\WhatsApp\Services\WhatsAppBotService;
use Tests\TestCase;

class ContactRedirectTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER = '2348011112222';

    private function vendor(?string $whatsapp = '08031234567'): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Crystal Wears',
            'whatsapp_number' => $whatsapp,
        ]);
    }

    private function links(): ContactLinkService
    {
        return app(ContactLinkService::class);
    }

    // ==================== Link minting ====================

    public function test_it_mints_an_opaque_contact_route(): void
    {
        $url = (string) $this->links()->urlFor($this->vendor(), ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->assertStringContainsString('/c/', $url);
        $this->assertStringNotContainsString('wa.me', $url);
        $this->assertMatchesRegularExpression('#/c/[A-Za-z0-9]{32}$#', $url);
    }

    public function test_the_token_leaks_nothing_about_the_buyer(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        $this->assertStringNotContainsString(self::BUYER, $url);
        $this->assertStringNotContainsString((string) $vendor->id, $token);

        // Nothing recoverable by decoding the token itself.
        $decoded = base64_decode(strtr($token, '-_', '+/'), false);
        $this->assertStringNotContainsString(self::BUYER, (string) $decoded);
        $this->assertNull(json_decode((string) $decoded, true));
    }

    public function test_two_links_never_share_a_token(): void
    {
        $vendor = $this->vendor();

        $first = $this->links()->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);
        $second = $this->links()->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->assertNotSame($first?->token, $second?->token);
    }

    public function test_it_mints_nothing_for_a_vendor_without_a_usable_number(): void
    {
        $this->assertNull($this->links()->urlFor($this->vendor(whatsapp: null), ViewSourceEnum::WHATSAPP, self::BUYER));
        $this->assertNull($this->links()->urlFor($this->vendor(whatsapp: '12345'), ViewSourceEnum::WHATSAPP, self::BUYER));
    }

    // ==================== Redirect behaviour ====================

    public function test_it_redirects_the_buyer_to_the_vendors_whatsapp(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->get($url)->assertRedirect('https://wa.me/2348031234567');
    }

    public function test_it_keeps_the_prefilled_message_intact(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor(
            $vendor,
            ViewSourceEnum::WEB,
            'visitor-abc',
            'Hi Crystal Wears, I found you on jiidaa.',
        );

        $response = $this->get($url);

        $response->assertStatus(302);
        $this->assertSame(
            'https://wa.me/2348031234567?text='.rawurlencode('Hi Crystal Wears, I found you on jiidaa.'),
            $response->headers->get('Location'),
        );
    }

    public function test_it_persists_the_click_with_vendor_source_buyer_and_timestamp(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->get($url);

        $click = ContactClick::firstOrFail();

        $this->assertSame($vendor->id, $click->vendor_id);
        $this->assertSame(ViewSourceEnum::WHATSAPP, $click->source);
        $this->assertSame(self::BUYER, $click->buyer_identity);
        $this->assertTrue($click->is_billable);
        $this->assertNotNull($click->created_at);
        $this->assertNotNull($click->link);
    }

    public function test_repeat_clicks_all_trace_back_to_one_link(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->get($url);
        $this->get($url);

        $this->assertDatabaseCount(ContactLink::getTableName(), 1);
        $this->assertSame(2, ContactClick::count());
        $this->assertSame(1, ContactClick::distinct()->count('contact_link_id'));
    }

    public function test_a_link_preview_fetch_is_not_recorded_as_a_click(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->head($url)->assertRedirect('https://wa.me/2348031234567');

        $this->assertDatabaseCount(ContactClick::getTableName(), 0);
    }

    public function test_it_emits_an_event_for_the_billing_listener(): void
    {
        Event::fake([VendorContactClicked::class]);

        $vendor = $this->vendor();
        $this->get((string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER));

        Event::assertDispatched(
            VendorContactClicked::class,
            fn (VendorContactClicked $event) => $event->click->vendor_id === $vendor->id
                && $event->click->is_billable === true,
        );
    }

    // ==================== Tampering and expiry ====================

    public function test_an_unknown_token_404s(): void
    {
        $this->vendor();

        $this->get('/c/'.str_repeat('a', 32))->assertNotFound();

        $this->assertDatabaseCount(ContactClick::getTableName(), 0);
    }

    public function test_a_malformed_token_404s(): void
    {
        $this->get('/c/not-a-real-token')->assertNotFound();
        $this->get('/c/'.str_repeat('a', 64))->assertNotFound();
        $this->assertDatabaseCount(ContactClick::getTableName(), 0);
    }

    public function test_an_expired_link_still_redirects_but_is_not_billable(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->travel(ContactLinkService::TOKEN_TTL_DAYS + 1)->days();

        $this->get($url)->assertRedirect('https://wa.me/2348031234567');

        $click = ContactClick::firstOrFail();
        $this->assertFalse($click->is_billable);
    }

    public function test_a_click_for_a_deleted_vendor_404s(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $vendor->delete();

        $this->get($url)->assertNotFound();
    }

    // ==================== Bot integration ====================

    public function test_the_bot_contact_card_links_through_the_redirect(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);

        $vendor = $this->vendor();

        WhatsAppSession::create([
            'phone_number' => self::BUYER,
            'state' => WhatsAppSessionStateEnum::SHOWING_PRODUCTS,
            'data' => ['selected_vendor_id' => $vendor->id, 'product_page' => 1],
            'last_active_at' => now(),
        ]);

        app(WhatsAppBotService::class)->handle(self::BUYER, 'text', 'CONTACT', []);

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            $body = (string) data_get($job->payload, 'text.body');

            return str_contains($body, '/c/') && ! str_contains($body, 'wa.me');
        });
    }
}
