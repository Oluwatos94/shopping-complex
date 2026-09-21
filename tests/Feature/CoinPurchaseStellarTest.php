<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Billing\Enums\AnchorTransactionKindEnum;
use ModulesShoppingComplex\Billing\Enums\CoinPurchaseStatusEnum;
use ModulesShoppingComplex\Billing\Models\AnchorTransaction;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;
use ModulesShoppingComplex\Billing\Payments\Stellar\StellarDepositService;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\WhatsApp\Jobs\SendWhatsAppMessage;
use Tests\TestCase;

class CoinPurchaseStellarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.coins.expiry_months' => 12]);
        config(['broadcasting.default' => 'null']);

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

    /** Swap the on-chain settlement for a fake so no network is touched. */
    private function fakeSettler(bool $succeeds = true): void
    {
        $fake = new class($succeeds) extends StellarDepositService
        {
            public function __construct(private bool $succeeds) {}

            public function settleNgncToPlatform(float $amount, string $memo): string
            {
                if (! $this->succeeds) {
                    throw new \RuntimeException('The on-chain payment could not be completed. Please try again.');
                }

                return 'txhash_'.md5($memo);
            }
        };

        $this->app->instance(StellarDepositService::class, $fake);
    }

    public function test_a_confirmed_purchase_settles_on_chain_credits_coins_and_logs_the_tx(): void
    {
        $this->fakeSettler();
        $vendor = $this->vendor();

        $this->actingAs($vendor)
            ->postJson('/vendor/coins/starter/stellar')
            ->assertOk()
            ->assertJson(['status' => 'completed', 'coins' => 100, 'balance' => 100])
            ->assertJsonStructure(['tx_hash']);

        $this->assertSame(100, $this->wallet()->balance($vendor));

        $purchase = CoinPurchase::sole();
        $this->assertSame(CoinPurchaseStatusEnum::COMPLETED, $purchase->status);

        $tx = AnchorTransaction::sole();
        $this->assertSame($purchase->id, $tx->coin_purchase_id);
        $this->assertSame(AnchorTransactionKindEnum::COIN_DEPOSIT, $tx->kind);
        $this->assertNotNull($tx->stellar_tx_hash);

        // Starter has no bonus, so a single purchase ledger entry.
        $this->assertSame(1, CoinLedgerEntry::count());
    }

    public function test_a_failed_settlement_credits_nothing_and_leaves_no_purchase(): void
    {
        $this->fakeSettler(succeeds: false);
        $vendor = $this->vendor();

        $this->actingAs($vendor)
            ->postJson('/vendor/coins/starter/stellar')
            ->assertStatus(422);

        $this->assertSame(0, $this->wallet()->balance($vendor));
        $this->assertSame(0, CoinPurchase::count());
        $this->assertSame(0, AnchorTransaction::count());
        $this->assertSame(0, CoinLedgerEntry::count());
    }

    public function test_a_non_vendor_cannot_settle(): void
    {
        $this->fakeSettler();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->postJson('/vendor/coins/growth/stellar')
            ->assertStatus(403);

        $this->assertSame(0, CoinPurchase::count());
    }

    public function test_an_unknown_pack_is_rejected(): void
    {
        $this->fakeSettler();

        $this->actingAs($this->vendor())
            ->postJson('/vendor/coins/enterprise/stellar')
            ->assertStatus(404);

        $this->assertSame(0, CoinPurchase::count());
    }
}
