<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Payments\AmountMismatchException;
use ModulesShoppingComplex\Billing\Payments\CheckoutSession;
use ModulesShoppingComplex\Billing\Payments\Contracts\PaymentProvider;
use ModulesShoppingComplex\Billing\Payments\PaymentProviderManager;
use ModulesShoppingComplex\Billing\Payments\PaymentResult;
use ModulesShoppingComplex\Billing\Repositories\SubscriptionRepository;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build a SubscriptionService whose (only) payment rail confirms a deposit that
     * settled for $settledAmount against $planId — standing in for the on-chain anchor.
     */
    private function serviceSettling(float $settledAmount, int $planId): SubscriptionService
    {
        $provider = new class($settledAmount, $planId) implements PaymentProvider
        {
            public function __construct(private float $amount, private int $planId) {}

            public function method(): PaymentMethodEnum
            {
                return PaymentMethodEnum::STELLAR;
            }

            public function supportsRecurring(): bool
            {
                return true;
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

    public function test_exact_amount_activates_subscription(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->plan(15000);

        $subscription = $this->serviceSettling(15000.0, $plan->id)
            ->handleCallback(PaymentMethodEnum::STELLAR, 'ref-exact', $vendor);

        $this->assertSame($plan->id, $subscription->plan_id);
        $this->assertSame('ref-exact', $subscription->payment_reference);
        $this->assertSame(15000.0, (float) $subscription->amount_paid);
    }

    public function test_underpayment_is_rejected(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->plan(15000);

        try {
            $this->serviceSettling(5000.0, $plan->id)
                ->handleCallback(PaymentMethodEnum::STELLAR, 'ref-under', $vendor);
            $this->fail('Expected AmountMismatchException for underpayment.');
        } catch (AmountMismatchException $e) {
            $this->assertSame(15000.0, $e->expected);
            $this->assertSame(5000.0, $e->actual);
        }

        $this->assertDatabaseMissing('vendor_subscriptions', ['payment_reference' => 'ref-under']);
    }

    public function test_overpayment_is_rejected(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $plan = $this->plan(5000);

        $this->expectException(AmountMismatchException::class);

        try {
            $this->serviceSettling(20000.0, $plan->id)
                ->handleCallback(PaymentMethodEnum::STELLAR, 'ref-over', $vendor);
        } finally {
            $this->assertDatabaseMissing('vendor_subscriptions', ['payment_reference' => 'ref-over']);
        }
    }
}
