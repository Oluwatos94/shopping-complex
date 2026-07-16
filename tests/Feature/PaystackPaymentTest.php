<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Jobs\SendWhatsAppMessage;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorSubscription;
use ModulesShoppingComplex\Services\Payments\CheckoutTypeEnum;
use ModulesShoppingComplex\Services\Payments\PaystackProvider;
use ModulesShoppingComplex\Services\PaystackClient;
use Tests\TestCase;

class PaystackPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret_key' => self::SECRET]);
        Queue::fake([SendWhatsAppMessage::class]);
    }

    private function client(): PaystackClient
    {
        return new PaystackClient(self::SECRET);
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

    private function fakeInitialize(string $authorizationUrl): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => $authorizationUrl],
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeVerify(array $overrides = [], int $httpStatus = 200, bool $apiStatus = true): void
    {
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => $apiStatus,
                'data' => array_merge([
                    'status' => 'success',
                    'reference' => 'PSK_ref',
                    'amount' => 500000,
                    'currency' => 'NGN',
                    'metadata' => ['vendor_id' => 1, 'plan_id' => 1],
                ], $overrides),
            ], $httpStatus),
        ]);
    }

    // ── PaystackClient ────────────────────────────────────────────────

    public function test_initialize_returns_the_authorization_url(): void
    {
        $this->fakeInitialize('https://checkout.paystack.com/abc123');

        $url = $this->client()->initializeTransaction('vendor@example.com', 500000, [], 'https://jiidaa.test/callback');

        $this->assertSame('https://checkout.paystack.com/abc123', $url);
    }

    public function test_initialize_http_failure_throws(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([], 500)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment initialization failed');

        $this->client()->initializeTransaction('vendor@example.com', 500000, [], 'https://jiidaa.test/callback');
    }

    public function test_initialize_api_status_false_throws(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => false])]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment initialization failed');

        $this->client()->initializeTransaction('vendor@example.com', 500000, [], 'https://jiidaa.test/callback');
    }

    public function test_initialize_rejects_non_paystack_authorization_url(): void
    {
        $this->fakeInitialize('https://evilpaystack.com/abc123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid payment gateway response');

        $this->client()->initializeTransaction('vendor@example.com', 500000, [], 'https://jiidaa.test/callback');
    }

    public function test_initialize_rejects_non_https_authorization_url(): void
    {
        $this->fakeInitialize('http://checkout.paystack.com/abc123');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid payment gateway response');

        $this->client()->initializeTransaction('vendor@example.com', 500000, [], 'https://jiidaa.test/callback');
    }

    public function test_verify_returns_the_transaction_data(): void
    {
        $this->fakeVerify(['amount' => 250000]);

        $data = $this->client()->verifyTransaction('PSK_ref');

        $this->assertSame('success', $data['status']);
        $this->assertSame(250000, $data['amount']);
    }

    public function test_verify_unsuccessful_charge_throws(): void
    {
        $this->fakeVerify(['status' => 'failed']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payment was not successful');

        $this->client()->verifyTransaction('PSK_ref');
    }

    public function test_verify_rejects_non_ngn_currency(): void
    {
        $this->fakeVerify(['currency' => 'USD']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unsupported currency');

        $this->client()->verifyTransaction('PSK_ref');
    }

    // ── PaystackProvider ──────────────────────────────────────────────

    public function test_checkout_sends_amount_in_kobo_with_metadata(): void
    {
        $this->fakeInitialize('https://checkout.paystack.com/abc123');
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->makePlan(5000.0);

        $session = (new PaystackProvider($this->client()))->startCheckout($vendor, $plan);

        $this->assertSame(CheckoutTypeEnum::REDIRECT, $session->type);
        $this->assertSame('https://checkout.paystack.com/abc123', $session->url);

        Http::assertSent(function (Request $request) use ($vendor, $plan) {
            return $request['amount'] === 500000
                && $request['email'] === $vendor->email
                && $request['metadata']['vendor_id'] === $vendor->id
                && $request['metadata']['plan_id'] === $plan->id;
        });
    }

    public function test_confirm_converts_kobo_to_naira(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $this->fakeVerify(['amount' => 500000, 'metadata' => ['vendor_id' => $vendor->id, 'plan_id' => 7]]);

        $result = (new PaystackProvider($this->client()))->confirm('PSK_ref', $vendor);

        $this->assertSame('PSK_ref', $result->reference);
        $this->assertSame(7, $result->planId);
        $this->assertSame(5000.0, $result->amountPaid);
    }

    public function test_confirm_rejects_missing_metadata(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $this->fakeVerify(['metadata' => []]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid payment metadata');

        (new PaystackProvider($this->client()))->confirm('PSK_ref', $vendor);
    }

    public function test_confirm_rejects_another_vendors_reference(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $other = User::factory()->create(['role' => 'vendor']);
        $this->fakeVerify(['metadata' => ['vendor_id' => $other->id, 'plan_id' => 7]]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not belong to your account');

        (new PaystackProvider($this->client()))->confirm('PSK_ref', $vendor);
    }

    // ── Callback endpoint ─────────────────────────────────────────────

    public function test_callback_activates_subscription_and_redirects_with_success(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->makePlan(5000.0);
        $this->fakeVerify([
            'reference' => 'PSK_cb_1',
            'amount' => 500000,
            'metadata' => ['vendor_id' => $vendor->id, 'plan_id' => $plan->id],
        ]);

        $response = $this->actingAs($vendor)
            ->get('/vendor/subscription/callback?reference=PSK_cb_1');

        $response->assertRedirect(route('vendor.subscription.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas(VendorSubscription::getTableName(), [
            'vendor_id' => $vendor->id,
            'plan_id' => $plan->id,
            'payment_reference' => 'PSK_cb_1',
            'status' => 'active',
            'payment_method' => 'paystack',
        ]);
    }

    public function test_callback_failed_verification_redirects_with_error(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $this->makePlan();
        $this->fakeVerify(apiStatus: false);

        $response = $this->actingAs($vendor)
            ->get('/vendor/subscription/callback?reference=PSK_cb_2');

        $response->assertRedirect(route('vendor.subscription.index'))
            ->assertSessionHas('error');

        $this->assertSame(0, VendorSubscription::query()->count());
    }

    public function test_callback_missing_reference_redirects_with_error(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        Http::fake();

        $response = $this->actingAs($vendor)
            ->get('/vendor/subscription/callback');

        $response->assertRedirect(route('vendor.subscription.index'))
            ->assertSessionHas('error', 'Invalid payment reference.');

        Http::assertNothingSent();
    }
}
