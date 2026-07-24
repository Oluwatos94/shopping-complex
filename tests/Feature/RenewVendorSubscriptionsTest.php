<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Billing\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Enums\Sep24StatusEnum;
use ModulesShoppingComplex\Billing\Enums\SubscriptionAuthorizationStatusEnum;
use ModulesShoppingComplex\Billing\Enums\VendorSubscriptionStatusEnum;
use ModulesShoppingComplex\Billing\Jobs\RenewVendorSubscriptions;
use ModulesShoppingComplex\Billing\Models\AnchorTransaction;
use ModulesShoppingComplex\Billing\Models\StellarWallet;
use ModulesShoppingComplex\Billing\Models\SubscriptionAuthorization;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Models\VendorSubscription;
use ModulesShoppingComplex\Billing\Payments\Stellar\ChargeFailedException;
use ModulesShoppingComplex\Billing\Payments\Stellar\Contracts\RecurringCharger;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;
use Tests\TestCase;

class RenewVendorSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Renewal notifications broadcast via Pusher/Reverb; use the null broadcaster in this
        // test so NotificationService::send() doesn't reach out over the network.
        config(['broadcasting.default' => 'null']);

        Queue::fake([SendWhatsAppMessage::class]);
    }

    /** A fake charge rail so the job never touches the network. */
    private function fakeCharger(bool $succeeds = true): RecurringCharger
    {
        return new class($succeeds) implements RecurringCharger
        {
            public int $calls = 0;

            public function __construct(private bool $succeeds) {}

            public function charge(StellarWallet $from, float $amount): string
            {
                $this->calls++;

                if (! $this->succeeds) {
                    throw new ChargeFailedException('insufficient balance');
                }

                return 'txhash-'.$this->calls.'-'.uniqid();
            }
        };
    }

    private function runJob(RecurringCharger $charger): void
    {
        $this->app->instance(RecurringCharger::class, $charger);
        $this->app->call([new RenewVendorSubscriptions, 'handle']);
    }

    /**
     * Set up a Stellar-paid subscription that is due for renewal (expired yesterday), with a
     * provisioned custodial wallet and an active consent. Returns [subscription, expiredAt].
     *
     * @return array{0: VendorSubscription, 1: Carbon}
     */
    private function dueStellarSubscription(
        float $price = 5000,
        ?float $cap = null,
        ?Carbon $consentValidUntil = null,
        SubscriptionAuthorizationStatusEnum $consentStatus = SubscriptionAuthorizationStatusEnum::ACTIVE,
    ): array {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $plan = SubscriptionPlan::create([
            'name' => 'Pro',
            'slug' => 'pro-'.uniqid(),
            'price' => $price,
            'product_limit' => 100,
            'search_priority' => 1,
            'features' => [],
            'is_active' => true,
        ]);

        $expiredAt = now()->subDay();

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'plan_id' => $plan->id,
            'status' => VendorSubscriptionStatusEnum::ACTIVE,
            'payment_method' => PaymentMethodEnum::STELLAR,
            'started_at' => now()->subMonth(),
            'expires_at' => $expiredAt,
            'payment_reference' => 'initial-'.uniqid(),
            'amount_paid' => $price,
        ]);

        StellarWallet::create([
            'vendor_id' => $vendor->id,
            'public_key' => 'GTEST'.str_pad((string) $vendor->id, 51, '0'),
            'encrypted_secret' => 'STEST'.str_pad((string) $vendor->id, 51, '0'),
            'network' => 'testnet',
        ]);

        SubscriptionAuthorization::create([
            'vendor_id' => $vendor->id,
            'plan_id' => $plan->id,
            'monthly_cap' => $cap ?? $price,
            'valid_until' => $consentValidUntil ?? now()->addYear(),
            'status' => $consentStatus,
            'consent_at' => now(),
        ]);

        return [$subscription, $expiredAt];
    }

    public function test_successful_charge_extends_subscription_and_records_ledger(): void
    {
        [$subscription, $expiredAt] = $this->dueStellarSubscription(price: 5000);
        $charger = $this->fakeCharger(succeeds: true);

        $this->runJob($charger);

        $this->assertSame(1, $charger->calls);

        $subscription->refresh();
        $this->assertSame(
            $expiredAt->copy()->addMonth()->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d'),
        );
        $this->assertSame(VendorSubscriptionStatusEnum::ACTIVE, $subscription->status);

        $charge = AnchorTransaction::where('vendor_id', $subscription->vendor_id)
            ->where('kind', AnchorTransactionKindEnum::MPP_CHARGE)
            ->firstOrFail();
        $this->assertTrue($charge->isCompleted());
        $this->assertNotNull($charge->stellar_tx_hash);
        $this->assertSame($charge->stellar_tx_hash, $subscription->payment_reference);
    }

    public function test_failed_charge_does_not_extend_and_marks_ledger_error(): void
    {
        [$subscription, $expiredAt] = $this->dueStellarSubscription(price: 5000);
        $charger = $this->fakeCharger(succeeds: false);

        $this->runJob($charger);

        $this->assertSame(1, $charger->calls);

        $subscription->refresh();
        $this->assertSame($expiredAt->format('Y-m-d'), $subscription->expires_at->format('Y-m-d'));

        $charge = AnchorTransaction::where('vendor_id', $subscription->vendor_id)
            ->where('kind', AnchorTransactionKindEnum::MPP_CHARGE)
            ->firstOrFail();
        $this->assertSame(Sep24StatusEnum::ERROR->value, $charge->status);
    }

    public function test_charge_over_monthly_cap_is_skipped(): void
    {
        [$subscription, $expiredAt] = $this->dueStellarSubscription(price: 5000, cap: 4999);
        $charger = $this->fakeCharger(succeeds: true);

        $this->runJob($charger);

        $this->assertSame(0, $charger->calls);

        $subscription->refresh();
        $this->assertSame($expiredAt->format('Y-m-d'), $subscription->expires_at->format('Y-m-d'));
        $this->assertDatabaseCount('anchor_transactions', 0);
    }

    public function test_expired_consent_is_skipped(): void
    {
        [$subscription, $expiredAt] = $this->dueStellarSubscription(
            price: 5000,
            consentValidUntil: now()->subDay(),
        );
        $charger = $this->fakeCharger(succeeds: true);

        $this->runJob($charger);

        $this->assertSame(0, $charger->calls);

        $subscription->refresh();
        $this->assertSame($expiredAt->format('Y-m-d'), $subscription->expires_at->format('Y-m-d'));
        $this->assertDatabaseCount('anchor_transactions', 0);
    }

    public function test_already_settled_charge_is_reconciled_without_recharging(): void
    {
        [$subscription, $expiredAt] = $this->dueStellarSubscription(price: 5000);

        // Simulate a prior run that charged on-chain but crashed before extending.
        AnchorTransaction::create([
            'vendor_id' => $subscription->vendor_id,
            'plan_id' => $subscription->plan_id,
            'kind' => AnchorTransactionKindEnum::MPP_CHARGE,
            'billing_period' => $expiredAt->format('Y-m-d'),
            'status' => Sep24StatusEnum::COMPLETED->value,
            'amount' => 5000,
            'stellar_tx_hash' => 'preexisting-hash',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $charger = $this->fakeCharger(succeeds: true);
        $this->runJob($charger);

        // No second charge, no duplicate ledger row, but the extension is finished idempotently.
        $this->assertSame(0, $charger->calls);
        $this->assertDatabaseCount('anchor_transactions', 1);

        $subscription->refresh();
        $this->assertSame(
            $expiredAt->copy()->addMonth()->format('Y-m-d'),
            $subscription->expires_at->format('Y-m-d'),
        );
        $this->assertSame('preexisting-hash', $subscription->payment_reference);
    }

    public function test_successful_renewal_sends_whatsapp_confirmation(): void
    {
        [$subscription] = $this->dueStellarSubscription(price: 5000);
        $subscription->vendor->update(['whatsapp_number' => '2348012345678']);

        $this->runJob($this->fakeCharger(succeeds: true));

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '2348012345678'
                && str_contains($job->payload['text']['body'], '₦5,000.00 is confirmed')
                && str_contains($job->payload['text']['body'], 'renewed for another month');
        });
    }

    public function test_failed_renewal_sends_whatsapp_warning(): void
    {
        [$subscription] = $this->dueStellarSubscription(price: 5000);
        $subscription->vendor->update(['whatsapp_number' => '2348012345678']);

        $this->runJob($this->fakeCharger(succeeds: false));

        Queue::assertPushed(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '2348012345678'
                && str_contains($job->payload['text']['body'], 'renewal failed');
        });
    }

    public function test_renewal_without_whatsapp_number_sends_nothing(): void
    {
        $this->dueStellarSubscription(price: 5000);

        $this->runJob($this->fakeCharger(succeeds: true));

        Queue::assertNotPushed(SendWhatsAppMessage::class);
    }
}
