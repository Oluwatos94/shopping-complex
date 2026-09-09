<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Models\ContactClick;
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

    public function test_it_mints_a_signed_contact_route(): void
    {
        $url = $this->links()->urlFor($this->vendor(), ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->assertNotNull($url);
        $this->assertStringContainsString('/c/', (string) $url);
        $this->assertStringContainsString('signature=', (string) $url);
        $this->assertStringNotContainsString('wa.me', (string) $url);
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
        $this->assertNotNull($click->token_issued_at);
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

    public function test_a_tampered_token_404s(): void
    {
        $vendor = $this->vendor();
        $url = (string) $this->links()->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $other = $this->vendor();
        $forged = $this->links()->urlFor($other, ViewSourceEnum::WHATSAPP, self::BUYER);

        // Swap in another vendor's token but keep the original signature.
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $forgedToken = basename((string) parse_url((string) $forged, PHP_URL_PATH));

        $this->get('/c/'.$forgedToken.'?'.http_build_query($query))->assertNotFound();

        $this->assertDatabaseCount(ContactClick::getTableName(), 0);
    }

    public function test_a_malformed_token_404s(): void
    {
        $this->get('/c/not-a-real-token')->assertNotFound();
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
