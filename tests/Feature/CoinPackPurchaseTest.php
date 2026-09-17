<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Billing\Events\CoinPackPurchased;
use ModulesShoppingComplex\Billing\Listeners\SendCoinPurchaseReceipt;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;
use ModulesShoppingComplex\Billing\Services\CoinPackRegistry;
use ModulesShoppingComplex\Billing\Services\CoinPurchaseService;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;
use Tests\TestCase;

class CoinPackPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret_key' => self::SECRET]);
        config(['billing.coins.expiry_months' => 12]);

        Queue::fake([SendWhatsAppMessage::class]);
    }

    private function vendor(): User
    {
        return User::factory()->create(['role' => 'vendor', 'whatsapp_number' => '08031234567']);
    }

    private function wallet(): CoinWalletService
    {
        return app(CoinWalletService::class);
    }

    private function pendingPurchase(User $vendor, string $pack = 'growth', string $reference = 'coin_ref_1'): CoinPurchase
    {
        $coinPack = app(CoinPackRegistry::class)->find($pack);

        return CoinPurchase::create([
            'vendor_id' => $vendor->id,
            'pack' => $coinPack->key,
            'price' => $coinPack->price,
            'coins' => $coinPack->coins,
            'bonus_coins' => $coinPack->bonusCoins,
            'reference' => $reference,
            'status' => CoinPurchaseStatusEnum::PENDING,
        ]);
    }

    private function fakeVerify(string $reference, int $amountInKobo, int $vendorId, string $status = 'success'): void
    {
        Http::fake([
            "api.paystack.co/transaction/verify/{$reference}" => Http::response([
                'status' => true,
                'data' => [
                    'status' => $status,
                    'reference' => $reference,
                    'amount' => $amountInKobo,
                    'currency' => 'NGN',
                    'metadata' => ['type' => CoinPurchaseService::CHANNEL, 'vendor_id' => $vendorId, 'pack' => 'growth'],
                ],
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function postWebhook(array $data, ?string $signature = null): TestResponse
    {
        $payload = ['event' => 'charge.success', 'data' => $data];
        $body = (string) json_encode($payload);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_PAYSTACK_SIGNATURE' => $signature ?? hash_hmac('sha512', $body, self::SECRET),
        ];

        return $this->call('POST', '/webhook/paystack', [], [], [], $headers, $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function coinWebhookData(string $reference, int $vendorId): array
    {
        return [
            'reference' => $reference,
            'metadata' => ['type' => CoinPurchaseService::CHANNEL, 'vendor_id' => $vendorId, 'pack' => 'growth'],
        ];
    }

    // ==================== Pack definitions ====================

    public function test_config_packs_match_the_published_rate_card(): void
    {
        $packs = app(CoinPackRegistry::class)->all();

        $this->assertSame(['starter', 'growth', 'scale'], array_keys($packs));

        $expected = [
            'starter' => ['price' => 5000, 'coins' => 100, 'bonus' => 0, 'total' => 100],
            'growth' => ['price' => 20000, 'coins' => 400, 'bonus' => 40, 'total' => 440],
            'scale' => ['price' => 50000, 'coins' => 1000, 'bonus' => 150, 'total' => 1150],
        ];

        foreach ($expected as $key => $e) {
            $pack = $packs[$key];
            $this->assertSame($e['price'], $pack->price, "{$key} price");
            $this->assertSame($e['coins'], $pack->coins, "{$key} base coins");
            $this->assertSame($e['bonus'], $pack->bonusCoins, "{$key} bonus");
            $this->assertSame($e['total'], $pack->totalCoins(), "{$key} total");
            // The coin holds a flat ₦50 value; the discount is bonus coins, not a cheaper coin.
            $this->assertSame($pack->coins, intdiv($pack->price, 50), "{$key} base rate is ₦50/coin");
        }
    }

    // ==================== Checkout ====================

    public function test_checkout_records_a_pending_purchase_and_calls_paystack(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123'],
            ]),
        ]);

        $vendor = $this->vendor();

        $this->actingAs($vendor)->post('/vendor/coins/growth')->assertRedirect();

        $purchase = CoinPurchase::sole();
        $this->assertSame($vendor->id, $purchase->vendor_id);
        $this->assertSame('growth', $purchase->pack);
        $this->assertSame(400, $purchase->coins);
        $this->assertSame(40, $purchase->bonus_coins);
        $this->assertSame(CoinPurchaseStatusEnum::PENDING, $purchase->status);

        // Paystack was asked to charge the pack price in kobo, tagged as a coin pack.
        Http::assertSent(function ($request) use ($purchase) {
            return str_contains($request->url(), 'transaction/initialize')
                && $request['amount'] === 2000000
                && $request['reference'] === $purchase->reference
                && $request['metadata']['type'] === CoinPurchaseService::CHANNEL;
        });
    }

    public function test_a_failed_paystack_init_leaves_no_pending_purchase(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => false], 400),
        ]);

        $this->actingAs($this->vendor())->post('/vendor/coins/growth')->assertRedirect();

        // A failed initialization must not litter the table with an orphan
        // that can never be paid or credited.
        $this->assertSame(0, CoinPurchase::count());
    }

    public function test_an_unknown_pack_cannot_be_checked_out(): void
    {
        $this->actingAs($this->vendor())->post('/vendor/coins/enterprise')->assertRedirect();

        $this->assertSame(0, CoinPurchase::count());
    }

    public function test_a_non_vendor_cannot_buy_coins(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->post('/vendor/coins/growth')->assertRedirect(route('home'));

        $this->assertSame(0, CoinPurchase::count());
    }

    // ==================== Webhook crediting ====================

    public function test_a_successful_webhook_credits_purchase_and_bonus_once(): void
    {
        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        $this->fakeVerify('coin_ref_1', 2000000, $vendor->id);

        $this->postWebhook($this->coinWebhookData('coin_ref_1', $vendor->id))->assertOk();

        $this->assertSame(440, $this->wallet()->balance($vendor));
        $this->assertSame(CoinPurchaseStatusEnum::COMPLETED, CoinPurchase::sole()->status);

        $purchaseEntry = CoinLedgerEntry::where('type', CoinLedgerTypeEnum::PURCHASE->value)->sole();
        $bonusEntry = CoinLedgerEntry::where('type', CoinLedgerTypeEnum::BONUS->value)->sole();
        $this->assertSame(400, $purchaseEntry->amount);
        $this->assertSame(40, $bonusEntry->amount);
    }

    public function test_a_replayed_webhook_does_not_double_credit(): void
    {
        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        $this->fakeVerify('coin_ref_1', 2000000, $vendor->id);

        $this->postWebhook($this->coinWebhookData('coin_ref_1', $vendor->id))->assertOk();
        $this->postWebhook($this->coinWebhookData('coin_ref_1', $vendor->id))->assertOk();

        $this->assertSame(440, $this->wallet()->balance($vendor));
        $this->assertSame(2, CoinLedgerEntry::count());
    }

    public function test_the_redirect_callback_and_a_racing_webhook_credit_once(): void
    {
        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        $this->fakeVerify('coin_ref_1', 2000000, $vendor->id);

        $this->actingAs($vendor)->get('/vendor/coins/callback?reference=coin_ref_1')->assertRedirect();
        $this->postWebhook($this->coinWebhookData('coin_ref_1', $vendor->id))->assertOk();

        $this->assertSame(440, $this->wallet()->balance($vendor));
        $this->assertSame(2, CoinLedgerEntry::count());
    }

    public function test_a_pack_without_a_bonus_creates_no_bonus_entry(): void
    {
        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'starter', 'coin_ref_starter');
        $this->fakeVerify('coin_ref_starter', 500000, $vendor->id);

        $this->postWebhook($this->coinWebhookData('coin_ref_starter', $vendor->id))->assertOk();

        $this->assertSame(100, $this->wallet()->balance($vendor));
        $this->assertSame(0, CoinLedgerEntry::where('type', CoinLedgerTypeEnum::BONUS->value)->count());
    }

    // ==================== Nothing credited on failure ====================

    public function test_an_unsuccessful_payment_credits_nothing(): void
    {
        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        $this->fakeVerify('coin_ref_1', 2000000, $vendor->id, status: 'failed');

        $this->postWebhook($this->coinWebhookData('coin_ref_1', $vendor->id))->assertOk();

        $this->assertSame(0, $this->wallet()->balance($vendor));
        $this->assertSame(CoinPurchaseStatusEnum::PENDING, CoinPurchase::sole()->status);
        $this->assertSame(0, CoinLedgerEntry::count());
    }

    public function test_a_tampered_amount_credits_nothing(): void
    {
        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        // Paid ₦5,000 but the Growth pack costs ₦20,000.
        $this->fakeVerify('coin_ref_1', 500000, $vendor->id);

        $this->postWebhook($this->coinWebhookData('coin_ref_1', $vendor->id))->assertOk();

        $this->assertSame(0, $this->wallet()->balance($vendor));
        $this->assertSame(CoinPurchaseStatusEnum::PENDING, CoinPurchase::sole()->status);
    }

    public function test_a_webhook_for_another_vendor_is_rejected(): void
    {
        $vendor = $this->vendor();
        $intruder = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        $this->fakeVerify('coin_ref_1', 2000000, $vendor->id);

        // Reference belongs to $vendor but the webhook claims $intruder.
        $this->postWebhook($this->coinWebhookData('coin_ref_1', $intruder->id))->assertOk();

        $this->assertSame(0, $this->wallet()->balance($vendor));
        $this->assertSame(0, $this->wallet()->balance($intruder));
        $this->assertSame(CoinPurchaseStatusEnum::PENDING, CoinPurchase::sole()->status);
    }

    public function test_an_unknown_reference_credits_nothing(): void
    {
        $vendor = $this->vendor();

        $this->postWebhook($this->coinWebhookData('coin_never_seen', $vendor->id))->assertOk();

        $this->assertSame(0, $this->wallet()->balance($vendor));
        $this->assertSame(0, CoinPurchase::count());
    }

    public function test_a_vendor_cannot_probe_another_vendors_completed_reference(): void
    {
        $owner = $this->vendor();
        $intruder = $this->vendor();

        // Owner's purchase is already completed.
        $this->pendingPurchase($owner, 'growth', 'coin_ref_owner')->forceFill([
            'status' => CoinPurchaseStatusEnum::COMPLETED,
            'paid_at' => now(),
        ])->save();

        $this->actingAs($intruder)
            ->get('/vendor/coins/callback?reference=coin_ref_owner')
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionMissing('success');
    }

    // ==================== Transient gateway failures ====================

    public function test_a_gateway_error_during_verify_defers_the_webhook_for_retry(): void
    {
        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response('upstream error', 502)]);

        // 503 tells Paystack to redeliver rather than treating a paid charge as done.
        $this->postWebhook($this->coinWebhookData('coin_ref_1', $vendor->id))->assertStatus(503);

        $this->assertSame(0, $this->wallet()->balance($vendor));
        $this->assertSame(CoinPurchaseStatusEnum::PENDING, CoinPurchase::sole()->status);
    }

    public function test_an_ambiguous_init_failure_keeps_the_pending_purchase(): void
    {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response('upstream error', 503)]);

        $this->actingAs($this->vendor())->post('/vendor/coins/growth')->assertRedirect();

        // Paystack may have created the transaction, so the row must survive for
        // a later payment's webhook to reconcile against.
        $this->assertSame(1, CoinPurchase::count());
        $this->assertSame(CoinPurchaseStatusEnum::PENDING, CoinPurchase::sole()->status);
    }

    // ==================== Receipt ====================

    public function test_a_receipt_event_is_dispatched_on_success(): void
    {
        Event::fake([CoinPackPurchased::class]);

        $vendor = $this->vendor();
        $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');
        $this->fakeVerify('coin_ref_1', 2000000, $vendor->id);

        app(CoinPurchaseService::class)->fulfill('coin_ref_1', $vendor);

        Event::assertDispatched(
            CoinPackPurchased::class,
            fn (CoinPackPurchased $e) => $e->purchase->vendor_id === $vendor->id && $e->newBalance === 440,
        );
    }

    public function test_the_receipt_names_the_coins_bonus_and_new_balance(): void
    {
        $vendor = $this->vendor();
        $purchase = $this->pendingPurchase($vendor, 'growth', 'coin_ref_1');

        $sender = new class implements WhatsAppSender
        {
            public string $body = '';

            public function sendText(string $to, string $body): void
            {
                $this->body = $body;
            }

            public function sendTemplate(string $to, string $templateName, string $lang, array $components = []): void {}
        };

        (new SendCoinPurchaseReceipt($sender))
            ->handle(new CoinPackPurchased($purchase, 640));

        $this->assertStringContainsString('440 coins', $sender->body);
        $this->assertStringContainsString('400 + 40 bonus', $sender->body);
        $this->assertStringContainsString('New balance: 640', $sender->body);
    }

    // ==================== Packs endpoint ====================

    public function test_the_coins_page_shows_packs_and_balance(): void
    {
        $vendor = $this->vendor();
        $this->wallet()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 25);

        $this->actingAs($vendor)->get('/vendor/coins')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Vendor/Coins', false)
                ->where('balance', 25)
                ->where('packs.1.key', 'growth')
                ->where('packs.1.total_coins', 440)
                ->has('packs.1.leads_at_rate')
        );
    }
}
