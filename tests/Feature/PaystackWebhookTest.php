<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use ModulesShoppingComplex\Jobs\SendWhatsAppMessage;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorSubscription;
use Tests\TestCase;

class PaystackWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret_key' => self::SECRET]);

        // The activation event queues a WhatsApp job; capture it so no real send happens.
        Queue::fake([SendWhatsAppMessage::class]);
    }

    private function makePlan(float $price = 5000.0): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro-'.uniqid(),
            'price' => $price,
            'product_limit' => 100,
            'search_priority' => 1,
            'features' => [],
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload, ?string $signature = null): TestResponse
    {
        $body = (string) json_encode($payload);

        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($signature !== '') {
            $headers['HTTP_X_PAYSTACK_SIGNATURE'] = $signature ?? hash_hmac('sha512', $body, self::SECRET);
        }

        return $this->call('POST', '/webhook/paystack', [], [], [], $headers, $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function chargeSuccessPayload(string $reference, int $vendorId, int $planId): array
    {
        return [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
                'metadata' => ['vendor_id' => $vendorId, 'plan_id' => $planId],
            ],
        ];
    }

    private function fakeVerify(string $reference, int $amountInKobo, int $vendorId, int $planId): void
    {
        Http::fake([
            "api.paystack.co/transaction/verify/{$reference}" => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'reference' => $reference,
                    'amount' => $amountInKobo,
                    'currency' => 'NGN',
                    'metadata' => ['vendor_id' => $vendorId, 'plan_id' => $planId],
                ],
            ]),
        ]);
    }

    public function test_valid_charge_success_activates_the_subscription(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->makePlan(5000.0);
        $this->fakeVerify('PSK_ref_1', 500000, $vendor->id, $plan->id);

        $response = $this->postWebhook($this->chargeSuccessPayload('PSK_ref_1', $vendor->id, $plan->id));

        $response->assertOk();
        $this->assertDatabaseHas(VendorSubscription::getTableName(), [
            'vendor_id' => $vendor->id,
            'plan_id' => $plan->id,
            'payment_reference' => 'PSK_ref_1',
            'status' => 'active',
            'payment_method' => 'paystack',
        ]);
    }

    public function test_invalid_signature_is_rejected_and_nothing_persisted(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->makePlan();
        Http::fake();

        $payload = $this->chargeSuccessPayload('PSK_ref_2', $vendor->id, $plan->id);

        $this->postWebhook($payload, signature: hash_hmac('sha512', 'tampered', self::SECRET))
            ->assertUnauthorized();

        $this->postWebhook($payload, signature: '')->assertUnauthorized();

        Http::assertNothingSent();
        $this->assertSame(0, VendorSubscription::query()->count());
    }

    public function test_duplicate_delivery_is_idempotent(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->makePlan(5000.0);
        $this->fakeVerify('PSK_ref_3', 500000, $vendor->id, $plan->id);

        $payload = $this->chargeSuccessPayload('PSK_ref_3', $vendor->id, $plan->id);

        $this->postWebhook($payload)->assertOk();
        $this->postWebhook($payload)->assertOk();

        $this->assertSame(1, VendorSubscription::query()->where('payment_reference', 'PSK_ref_3')->count());
    }

    public function test_other_event_types_are_acknowledged_without_processing(): void
    {
        Http::fake();

        $this->postWebhook(['event' => 'transfer.success', 'data' => ['reference' => 'PSK_ref_4']])
            ->assertOk();

        Http::assertNothingSent();
        $this->assertSame(0, VendorSubscription::query()->count());
    }

    public function test_unknown_vendor_is_acknowledged_without_processing(): void
    {
        $plan = $this->makePlan();
        Http::fake();

        $this->postWebhook($this->chargeSuccessPayload('PSK_ref_5', 999999, $plan->id))
            ->assertOk();

        Http::assertNothingSent();
        $this->assertSame(0, VendorSubscription::query()->count());
    }

    public function test_amount_mismatch_is_acknowledged_but_not_activated(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->makePlan(5000.0);
        // Paid ₦1,000 against a ₦5,000 plan.
        $this->fakeVerify('PSK_ref_6', 100000, $vendor->id, $plan->id);

        $this->postWebhook($this->chargeSuccessPayload('PSK_ref_6', $vendor->id, $plan->id))
            ->assertOk();

        $this->assertSame(0, VendorSubscription::query()->count());
    }
}
