<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class VendorWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.leads.default_cost' => 5, 'billing.leads.low_balance_leads' => 3, 'billing.coins.expiry_months' => 12]);
    }

    private function vendorOnTier(int $tierCost): User
    {
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics-'.uniqid(), 'lead_coin_cost' => $tierCost]);

        return User::factory()->create(['role' => 'vendor', 'category_id' => $category->id]);
    }

    public function test_the_wallet_reports_balance_rate_and_leads_affordable(): void
    {
        $vendor = $this->vendorOnTier(20);
        $wallet = app(CoinWalletService::class);
        $wallet->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);
        $wallet->debit($vendor, 20, BillableLead::create([
            'vendor_id' => $vendor->id,
            'buyer_identity' => '2348011112222',
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => 20,
            'state' => BillableLeadStateEnum::CHARGED,
            'window_start' => now(),
        ]));

        $this->actingAs($vendor)->get('/vendor/wallet')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Vendor/Wallet', false)
                ->where('balance', 80)
                ->where('lead_rate', 20)
                ->where('leads_affordable', 4)
                ->where('category.name', 'Electronics')
                ->where('category.cost', 20)
                ->where('low_balance', false)
        );
    }

    public function test_the_ledger_reconciles_with_the_balance(): void
    {
        $vendor = $this->vendorOnTier(10);
        $wallet = app(CoinWalletService::class);
        $wallet->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 50);

        $this->actingAs($vendor)->get('/vendor/wallet')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('balance', 50)
                ->where('ledger.data.0.balance_after', 50)
                ->where('ledger.data.0.type', 'purchase')
        );
    }

    public function test_it_surfaces_leads_missed_while_out_of_coins(): void
    {
        $vendor = $this->vendorOnTier(10);
        BillableLead::create([
            'vendor_id' => $vendor->id,
            'buyer_identity' => '2348011112222',
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => 0,
            'state' => BillableLeadStateEnum::UNBILLED,
            'unbilled_reason' => LeadUnbilledReasonEnum::INSUFFICIENT_BALANCE,
            'window_start' => now(),
        ]);

        $this->actingAs($vendor)->get('/vendor/wallet')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('unbilled_out_of_coins', 1)
                ->where('low_balance', true)
        );
    }

    public function test_a_non_vendor_cannot_open_the_wallet(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->get('/vendor/wallet')->assertRedirect();
    }
}
