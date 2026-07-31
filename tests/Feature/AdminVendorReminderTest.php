<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Models\VendorSubscription;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Models\VendorOnboarding;
use ModulesShoppingComplex\Notifications\Data\VendorUpdate;
use ModulesShoppingComplex\Notifications\Jobs\SendVendorReminder;
use ModulesShoppingComplex\Notifications\VendorUpdateMail;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;
use Tests\TestCase;

class AdminVendorReminderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'target' => 'all',
            'subject' => 'Renew your subscription',
            'body' => 'Your plan expires soon — renew to stay listed.',
        ], $overrides);
    }

    private function vendor(?string $whatsapp = '2348012345678'): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'whatsapp_number' => $whatsapp,
            'email_verified_at' => now(),
        ]);
    }

    private function vendorWithOnboarding(VendorOnboardingStatusEnum $status): User
    {
        $vendor = $this->vendor();
        VendorOnboarding::create([
            'user_id' => $vendor->id,
            'status' => $status,
            'current_step' => 3,
            'agreed_to_terms' => true,
        ]);

        return $vendor;
    }

    // ==================== Authorization ====================

    public function test_guest_cannot_send_reminders(): void
    {
        $this->postJson('/admin/vendors/reminders', $this->payload())->assertStatus(401);
    }

    public function test_non_admin_cannot_send_reminders(): void
    {
        Queue::fake();
        $customer = User::factory()->create(['role' => 'customer', 'email_verified_at' => now()]);

        $this->actingAs($customer)
            ->postJson('/admin/vendors/reminders', $this->payload())
            ->assertStatus(403);

        Queue::assertNothingPushed();
    }

    // ==================== Fan-out ====================

    public function test_target_all_queues_a_job_for_every_vendor_only(): void
    {
        Queue::fake([SendVendorReminder::class]);

        $v1 = $this->vendor();
        $v2 = $this->vendor();
        User::factory()->create(['role' => 'customer']); // must be ignored

        $this->actingAs($this->admin)
            ->post('/admin/vendors/reminders', $this->payload())
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(SendVendorReminder::class, 2);
        Queue::assertPushed(SendVendorReminder::class, fn (SendVendorReminder $j) => $j->vendor->id === $v1->id);
        Queue::assertPushed(SendVendorReminder::class, fn (SendVendorReminder $j) => $j->vendor->id === $v2->id);
    }

    public function test_target_selection_queues_only_selected_vendors(): void
    {
        Queue::fake([SendVendorReminder::class]);

        $chosen = $this->vendor();
        $other = $this->vendor();

        $this->actingAs($this->admin)
            ->post('/admin/vendors/reminders', $this->payload([
                'target' => 'selection',
                'vendor_ids' => [$chosen->id],
            ]))
            ->assertRedirect();

        Queue::assertPushed(SendVendorReminder::class, 1);
        Queue::assertPushed(SendVendorReminder::class, fn (SendVendorReminder $j) => $j->vendor->id === $chosen->id);
        Queue::assertNotPushed(SendVendorReminder::class, fn (SendVendorReminder $j) => $j->vendor->id === $other->id);
    }

    public function test_target_status_filters_by_onboarding_status(): void
    {
        Queue::fake([SendVendorReminder::class]);

        $approved = $this->vendorWithOnboarding(VendorOnboardingStatusEnum::APPROVED);
        $this->vendorWithOnboarding(VendorOnboardingStatusEnum::PENDING_REVIEW);

        $this->actingAs($this->admin)
            ->post('/admin/vendors/reminders', $this->payload([
                'target' => 'status',
                'status' => 'approved',
            ]))
            ->assertRedirect();

        Queue::assertPushed(SendVendorReminder::class, 1);
        Queue::assertPushed(SendVendorReminder::class, fn (SendVendorReminder $j) => $j->vendor->id === $approved->id);
    }

    public function test_target_status_subscription_expiring_filters_by_expiry_window(): void
    {
        Queue::fake([SendVendorReminder::class]);

        $plan = SubscriptionPlan::create([
            'name' => 'Basic', 'slug' => 'basic-'.uniqid(), 'price' => 5000,
            'product_limit' => 30, 'search_priority' => 1, 'features' => [], 'is_active' => true,
        ]);

        $expiring = $this->vendor();
        VendorSubscription::create([
            'vendor_id' => $expiring->id, 'plan_id' => $plan->id,
            'status' => VendorSubscriptionStatusEnum::ACTIVE, 'payment_method' => PaymentMethodEnum::STELLAR,
            'started_at' => now()->subMonth(), 'expires_at' => now()->addDays(3),
        ]);

        $healthy = $this->vendor();
        VendorSubscription::create([
            'vendor_id' => $healthy->id, 'plan_id' => $plan->id,
            'status' => VendorSubscriptionStatusEnum::ACTIVE, 'payment_method' => PaymentMethodEnum::STELLAR,
            'started_at' => now(), 'expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($this->admin)
            ->post('/admin/vendors/reminders', $this->payload([
                'target' => 'status',
                'status' => 'subscription_expiring',
            ]))
            ->assertRedirect();

        Queue::assertPushed(SendVendorReminder::class, 1);
        Queue::assertPushed(SendVendorReminder::class, fn (SendVendorReminder $j) => $j->vendor->id === $expiring->id);
    }

    public function test_no_matching_vendors_reports_error_and_queues_nothing(): void
    {
        Queue::fake([SendVendorReminder::class]);

        $this->actingAs($this->admin)
            ->post('/admin/vendors/reminders', $this->payload([
                'target' => 'selection',
                'vendor_ids' => [$this->admin->id], // an admin id — never a vendor
            ]))
            ->assertRedirect()
            ->assertSessionHas('error');

        Queue::assertNothingPushed();
    }

    // ==================== Validation ====================

    public function test_it_validates_required_message_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/admin/vendors/reminders', ['target' => 'all'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject', 'body']);
    }

    public function test_cta_url_requires_a_label(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/admin/vendors/reminders', $this->payload([
                'cta_url' => 'https://jiidaa.test/renew',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cta_label');
    }

    // ==================== Delivery (skip logic through the real service) ====================

    public function test_queued_reminder_fans_out_across_channels(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Mail::fake();

        $vendor = $this->vendor();

        SendVendorReminder::dispatchSync($vendor, new VendorUpdate(
            subject: 'Renew now',
            body: 'Please renew.',
            templateComponents: [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $vendor->name]]]],
        ));

        Queue::assertPushed(SendWhatsAppMessage::class, fn (SendWhatsAppMessage $j) => $j->payload['type'] === 'template');
        Mail::assertQueued(VendorUpdateMail::class);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'vendor_update']);
    }

    public function test_queued_reminder_skips_whatsapp_when_vendor_has_no_number(): void
    {
        Queue::fake([SendWhatsAppMessage::class]);
        Mail::fake();

        $vendor = $this->vendor(whatsapp: null);

        SendVendorReminder::dispatchSync($vendor, new VendorUpdate(
            subject: 'Renew now',
            body: 'Please renew.',
        ));

        Queue::assertNotPushed(SendWhatsAppMessage::class);
        Mail::assertQueued(VendorUpdateMail::class);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'vendor_update']);
    }
}
