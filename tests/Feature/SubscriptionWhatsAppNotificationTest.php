<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Jobs\SendWhatsAppMessage;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\SubscriptionRepository;
use ModulesShoppingComplex\Services\Payments\CheckoutSession;
use ModulesShoppingComplex\Services\Payments\Contracts\PaymentProvider;
use ModulesShoppingComplex\Services\Payments\PaymentProviderManager;
use ModulesShoppingComplex\Services\Payments\PaymentResult;
use ModulesShoppingComplex\Services\SubscriptionService;
use Tests\TestCase;

/**
 * The checkout side of Deliverable 3: a settled Stellar deposit fires
 * SubscriptionPaymentSucceeded, whose queued listener WhatsApps the vendor.
 * (The renewal side is covered in RenewVendorSubscriptionsTest.)
 */
class SubscriptionWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Capture only the WhatsApp job; the queued listener itself still runs
        // (sync queue), exactly as it would in production.
        Queue::fake([SendWhatsAppMessage::class]);
    }

    /** A SubscriptionService whose only rail settles $amount for $planId on $method. */
    private function serviceSettling(float $amount, int $planId, PaymentMethodEnum $method): SubscriptionService
    {
        $provider = new class($amount, $planId, $method) implements PaymentProvider
        {
            public function __construct(
                private float $amount,
                private int $planId,
                private PaymentMethodEnum $method,
            ) {}

            public function method(): PaymentMethodEnum
            {
                return $this->method;
            }

            public function supportsRecurring(): bool
            {
                return false;
            }

            public function startCheckout(User $vendor, SubscriptionPlan $plan): CheckoutSession
            {
                throw new \LogicException('not used in this test');
            }

            public function confirm(string $reference, User $vendor): PaymentResult
            {
                return new PaymentResult($reference, $this->planId, $this->amount);
            }
        };

        return new SubscriptionService(
            app(SubscriptionRepository::class),
            new PaymentProviderManager([$provider]),
        );
    }

    private function plan(float $price): SubscriptionPlan
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

    public function test_stellar_activation_sends_whatsapp_confirmation(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'whatsapp_number' => '2348012345678']);
        $plan = $this->plan(5000);

        $this->serviceSettling(5000.0, $plan->id, PaymentMethodEnum::STELLAR)
            ->handleCallback(PaymentMethodEnum::STELLAR, 'ref-wa-1', $vendor);

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '2348012345678'
                && str_contains($job->payload['text']['body'], '₦5,000.00 is confirmed')
                && str_contains($job->payload['text']['body'], 'now active');
        });
    }

    public function test_replayed_callback_notifies_only_once(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'whatsapp_number' => '2348012345678']);
        $plan = $this->plan(5000);
        $service = $this->serviceSettling(5000.0, $plan->id, PaymentMethodEnum::STELLAR);

        $service->handleCallback(PaymentMethodEnum::STELLAR, 'ref-wa-2', $vendor);
        $service->handleCallback(PaymentMethodEnum::STELLAR, 'ref-wa-2', $vendor);

        Queue::assertPushed(SendWhatsAppMessage::class, 1);
    }

    public function test_vendor_without_whatsapp_number_is_skipped(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'whatsapp_number' => null]);
        $plan = $this->plan(5000);

        $this->serviceSettling(5000.0, $plan->id, PaymentMethodEnum::STELLAR)
            ->handleCallback(PaymentMethodEnum::STELLAR, 'ref-wa-3', $vendor);

        Queue::assertNotPushed(SendWhatsAppMessage::class);
    }

    public function test_paystack_activation_does_not_whatsapp(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'whatsapp_number' => '2348012345678']);
        $plan = $this->plan(5000);

        $this->serviceSettling(5000.0, $plan->id, PaymentMethodEnum::PAYSTACK)
            ->handleCallback(PaymentMethodEnum::PAYSTACK, 'ref-wa-4', $vendor);

        Queue::assertNotPushed(SendWhatsAppMessage::class);
    }
}
