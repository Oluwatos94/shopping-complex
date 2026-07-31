<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;
use ModulesShoppingComplex\Notifications\Events\VendorUpdateEvent;
use ModulesShoppingComplex\Notifications\Repositories\NotificationPreferenceRepository;
use ModulesShoppingComplex\Notifications\Services\VendorUpdateService;
use ModulesShoppingComplex\Notifications\VendorUpdateMail;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;
use ModulesShoppingComplex\WhatsApp\Services\WhatsAppApiService;
use Tests\TestCase;

class VendorUpdateServiceTest extends TestCase
{
    use RefreshDatabase;

    private function update(): VendorUpdate
    {
        return new VendorUpdate(
            subject: 'Your store needs attention',
            body: 'You have 3 products low on stock.',
            ctaLabel: 'Review stock',
            ctaUrl: 'https://jiidaa.test/vendor/products',
            templateComponents: [
                ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => '3']]],
            ],
            data: ['action' => 'low_stock'],
        );
    }

    private function vendor(?string $whatsapp = '2348012345678'): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'whatsapp_number' => $whatsapp,
        ]);
    }

    // ==================== sendTemplate payload shape ====================

    public function test_send_template_produces_a_type_template_payload(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);

        app(WhatsAppApiService::class)->sendTemplate(
            '2348012345678',
            'vendor_update',
            'en',
            [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => '3']]]],
        );

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '2348012345678'
                && $job->payload['type'] === 'template'
                && $job->payload['template']['name'] === 'vendor_update'
                && $job->payload['template']['language'] === ['code' => 'en']
                && $job->payload['template']['components'][0]['type'] === 'body';
        });
    }

    public function test_send_template_omits_components_when_none_given(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);

        app(WhatsAppApiService::class)->sendTemplate('2348012345678', 'vendor_update', 'en');

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return ! array_key_exists('components', $job->payload['template']);
        });
    }

    // ==================== Channel fan-out ====================

    public function test_it_fans_out_to_whatsapp_email_and_in_app(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Mail::fake();
        Event::fake([VendorUpdateEvent::class]);

        $vendor = $this->vendor();

        app(VendorUpdateService::class)->send($vendor, $this->update());

        Queue::assertPushed(SendWhatsAppMessage::class, fn (SendWhatsAppMessage $job) => $job->payload['type'] === 'template'
            && $job->payload['template']['name'] === config('services.whatsapp.templates.vendor_update'));

        Mail::assertQueued(VendorUpdateMail::class, fn (VendorUpdateMail $mail) => $mail->hasTo($vendor->email));

        Event::assertDispatched(VendorUpdateEvent::class);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->id,
            'type' => 'vendor_update',
            'message' => 'You have 3 products low on stock.',
        ]);
    }

    public function test_it_skips_whatsapp_when_number_is_empty(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Mail::fake();

        $vendor = $this->vendor(whatsapp: null);

        app(VendorUpdateService::class)->send($vendor, $this->update());

        Queue::assertNotPushed(SendWhatsAppMessage::class);

        // The other channels still fire.
        Mail::assertQueued(VendorUpdateMail::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->id,
            'type' => 'vendor_update',
        ]);
    }

    public function test_it_skips_email_when_disabled_by_preference(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Mail::fake();

        $vendor = $this->vendor();

        app(NotificationPreferenceRepository::class)->updateOrCreate($vendor->id, 'vendor_update', [
            'email_enabled' => false,
        ]);

        app(VendorUpdateService::class)->send($vendor, $this->update());

        Mail::assertNothingQueued();

        // WhatsApp + in-app are unaffected.
        Queue::assertPushed(SendWhatsAppMessage::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->id,
            'type' => 'vendor_update',
        ]);
    }

    public function test_it_skips_in_app_when_disabled_by_preference(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Mail::fake();

        $vendor = $this->vendor();

        app(NotificationPreferenceRepository::class)->updateOrCreate($vendor->id, 'vendor_update', [
            'in_app_enabled' => false,
        ]);

        app(VendorUpdateService::class)->send($vendor, $this->update());

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $vendor->id,
            'type' => 'vendor_update',
        ]);

        // WhatsApp + email are unaffected.
        Queue::assertPushed(SendWhatsAppMessage::class);
        Mail::assertQueued(VendorUpdateMail::class);
    }

    public function test_one_channel_failing_does_not_block_the_others(): void
    {
        Mail::fake();

        // A WhatsApp sender that always throws.
        $this->app->bind(WhatsAppSender::class, fn () => new class implements WhatsAppSender
        {
            public function sendText(string $to, string $body): void {}

            public function sendTemplate(string $to, string $templateName, string $lang, array $components = []): void
            {
                throw new \RuntimeException('WhatsApp is down');
            }
        });

        $vendor = $this->vendor();

        app(VendorUpdateService::class)->send($vendor, $this->update());

        // Email + in-app still delivered despite the WhatsApp failure.
        Mail::assertQueued(VendorUpdateMail::class);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $vendor->id,
            'type' => 'vendor_update',
        ]);
    }
}
