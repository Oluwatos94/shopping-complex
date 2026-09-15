<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\CoinLedgerTypeEnum;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Exceptions\InsufficientCoinsException;
use ModulesShoppingComplex\Billing\Exceptions\LedgerIsAppendOnlyException;
use ModulesShoppingComplex\Billing\Jobs\ExpireCoins;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\CoinLedgerEntry;
use ModulesShoppingComplex\Billing\Models\CoinWallet;
use ModulesShoppingComplex\Billing\Repositories\CoinLedgerRepository;
use ModulesShoppingComplex\Billing\Services\CoinWalletService;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Billing\Services\LeadBillingService;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class CoinWalletTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER = '2348011112222';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'billing.leads.default_cost' => 10,
            'billing.coins.expiry_months' => 12,
        ]);
    }

    private function vendor(): User
    {
        return User::factory()->create(['role' => 'vendor', 'whatsapp_number' => '08031234567']);
    }

    private function wallets(): CoinWalletService
    {
        return app(CoinWalletService::class);
    }

    private function ledger(): CoinLedgerRepository
    {
        return app(CoinLedgerRepository::class);
    }

    // ==================== Movements ====================

    public function test_a_purchase_credits_the_wallet_and_appends_a_dated_entry(): void
    {
        $this->travelTo(Carbon::parse('2026-09-10 12:00:00'));
        $vendor = $this->vendor();

        $entry = $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $this->assertSame(100, $this->wallets()->balance($vendor));
        $this->assertSame(CoinLedgerTypeEnum::PURCHASE, $entry->type);
        $this->assertSame(100, $entry->amount);
        $this->assertSame(100, $entry->balance_after);
        $this->assertTrue($entry->expires_at?->equalTo(Carbon::parse('2027-09-10 12:00:00')));
    }

    public function test_a_debit_takes_coins_and_records_what_it_paid_for(): void
    {
        $vendor = $this->vendor();
        $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $lead = BillableLead::create([
            'vendor_id' => $vendor->id,
            'buyer_identity' => self::BUYER,
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => 0,
            'state' => BillableLeadStateEnum::UNBILLED,
            'window_start' => now(),
        ]);

        $entries = $this->wallets()->debit($vendor, 10, $lead);

        $this->assertCount(1, $entries);
        $entry = $entries->sole();

        $this->assertSame(90, $this->wallets()->balance($vendor));
        $this->assertSame(-10, $entry->amount);
        $this->assertSame(90, $entry->balance_after);
        $this->assertNull($entry->expires_at);
        $this->assertTrue($entry->reference?->is($lead));
        $this->assertNotNull($entry->lot_id);
    }

    public function test_a_debit_spanning_two_lots_names_each_one(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15'));
        $january = $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 10);

        $this->travelTo(Carbon::parse('2026-02-15'));
        $february = $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 10);

        $entries = $w->debit($vendor, 15);

        $this->assertCount(2, $entries);
        $this->assertSame([-10, -5], $entries->pluck('amount')->all());
        $this->assertSame([$january->id, $february->id], $entries->pluck('lot_id')->all());
        $this->assertSame(5, $w->balance($vendor));
    }

    public function test_the_balance_is_always_the_replayed_ledger(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);
        $w->credit($vendor, CoinLedgerTypeEnum::BONUS, 20);
        $w->debit($vendor, 35);
        $w->credit($vendor, CoinLedgerTypeEnum::PROMO, 5);
        $w->debit($vendor, 40);

        $this->assertSame(50, $w->balance($vendor));
        $this->assertSame(50, $this->ledger()->replayBalance($vendor->id));

        $chain = CoinLedgerEntry::where('vendor_id', $vendor->id)->orderBy('id')->pluck('balance_after')->all();
        $this->assertSame([100, 120, 85, 90, 50], $chain);
    }

    // ==================== Guards ====================

    public function test_a_debit_beyond_the_balance_is_refused_and_leaves_no_trace(): void
    {
        $vendor = $this->vendor();
        $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 30);

        try {
            $this->wallets()->debit($vendor, 31);
            $this->fail('expected the overdraft to be refused');
        } catch (InsufficientCoinsException $e) {
            $this->assertSame(30, $e->balance);
            $this->assertSame(31, $e->requested);
        }

        $this->assertSame(30, $this->wallets()->balance($vendor));
        $this->assertSame(1, CoinLedgerEntry::where('vendor_id', $vendor->id)->count());
    }

    public function test_the_balance_can_be_spent_to_exactly_zero_but_not_below(): void
    {
        $vendor = $this->vendor();
        $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 30);

        $this->wallets()->debit($vendor, 30);
        $this->assertSame(0, $this->wallets()->balance($vendor));

        $this->expectException(InsufficientCoinsException::class);
        $this->wallets()->debit($vendor, 1);
    }

    public function test_a_non_adding_type_cannot_be_used_to_credit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->wallets()->credit($this->vendor(), CoinLedgerTypeEnum::DEBIT, 10);
    }

    public function test_a_credit_cannot_be_issued_outside_a_refund(): void
    {
        // Otherwise a bare CREDIT would be indistinguishable from a real refund
        // and would show up in the refunded column of the finance report.
        $this->expectException(InvalidArgumentException::class);
        $this->wallets()->credit($this->vendor(), CoinLedgerTypeEnum::CREDIT, 10);
    }

    public function test_every_credit_entry_names_the_charge_it_reverses(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $lead = $this->lead($vendor);
        $w->debit($vendor, 10, $lead);
        $w->refund($vendor, $lead);

        $credits = CoinLedgerEntry::where('type', CoinLedgerTypeEnum::CREDIT->value)->get();

        $this->assertCount(1, $credits);
        $this->assertSame(0, $credits->whereNull('reference_id')->count());
    }

    public function test_amounts_must_be_positive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->wallets()->debit($this->vendor(), 0);
    }

    public function test_the_ledger_cannot_be_rewritten(): void
    {
        $entry = $this->wallets()->credit($this->vendor(), CoinLedgerTypeEnum::PURCHASE, 100);

        try {
            $entry->update(['amount' => 999]);
            $this->fail('expected the ledger to refuse an edit');
        } catch (LedgerIsAppendOnlyException) {
        }

        try {
            $entry->delete();
            $this->fail('expected the ledger to refuse a delete');
        } catch (LedgerIsAppendOnlyException) {
        }

        $this->assertSame(100, $entry->fresh()?->amount);
        $this->assertSame(1, CoinLedgerEntry::count());
    }

    public function test_the_ledger_cannot_be_rewritten_in_bulk_either(): void
    {
        $vendor = $this->vendor();
        $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        // Bulk writes never hydrate a model, so they miss the model event guards.
        try {
            CoinLedgerEntry::where('vendor_id', $vendor->id)->update(['amount' => 999]);
            $this->fail('expected a bulk update to be refused');
        } catch (LedgerIsAppendOnlyException) {
        }

        try {
            CoinLedgerEntry::where('vendor_id', $vendor->id)->delete();
            $this->fail('expected a bulk delete to be refused');
        } catch (LedgerIsAppendOnlyException) {
        }

        try {
            CoinLedgerEntry::where('vendor_id', $vendor->id)->increment('amount');
            $this->fail('expected a bulk increment to be refused');
        } catch (LedgerIsAppendOnlyException) {
        }

        $this->assertSame(1, CoinLedgerEntry::count());
        $this->assertSame(100, CoinLedgerEntry::sole()->amount);
    }

    public function test_reading_a_balance_does_not_create_a_wallet(): void
    {
        $vendor = $this->vendor();

        $this->assertSame(0, $this->wallets()->balance($vendor));
        $this->assertSame(0, CoinWallet::where('vendor_id', $vendor->id)->count());
    }

    // ==================== Expiry (FIFO) ====================

    public function test_coins_expire_oldest_first_after_twelve_months(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15 09:00'));
        $january = $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $this->travelTo(Carbon::parse('2026-07-15 09:00'));
        $july = $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $w->debit($vendor, 150);
        $this->assertSame(50, $w->balance($vendor));

        // January's lot was fully consumed by the debit, so nothing expires with it.
        $this->travelTo(Carbon::parse('2027-01-16'));
        $this->assertSame(0, $w->expire($vendor));
        $this->assertSame(50, $w->balance($vendor));

        // July's lot still holds the 50 the debit did not reach.
        $this->travelTo(Carbon::parse('2027-07-16'));
        $this->assertSame(50, $w->expire($vendor));
        $this->assertSame(0, $w->balance($vendor));
        $this->assertSame(0, $this->ledger()->replayBalance($vendor->id));

        $expiry = CoinLedgerEntry::where('type', CoinLedgerTypeEnum::EXPIRY->value)->sole();
        $this->assertSame(-50, $expiry->amount);
        $this->assertSame($july->id, $expiry->lot_id);
        $this->assertNotSame($january->id, $expiry->lot_id);
    }

    public function test_expiry_is_idempotent_and_leaves_unexpired_lots_alone(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 40);

        $this->travelTo(Carbon::parse('2026-12-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PROMO, 25);

        $this->travelTo(Carbon::parse('2027-02-01'));
        $this->assertSame(40, $w->expire($vendor));
        $this->assertSame(0, $w->expire($vendor));
        $this->assertSame(25, $w->balance($vendor));
    }

    public function test_debits_after_an_expiry_draw_from_the_next_lot(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 40);

        $this->travelTo(Carbon::parse('2026-06-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 40);

        $this->travelTo(Carbon::parse('2027-02-01'));
        $w->expire($vendor);
        $w->debit($vendor, 30);

        // Only the June lot is left, with 10 unspent; the expired January lot must not be reopened.
        $this->travelTo(Carbon::parse('2027-07-01'));
        $this->assertSame(10, $w->expire($vendor));
        $this->assertSame(0, $w->balance($vendor));
    }

    public function test_a_debit_cannot_spend_coins_past_their_deadline(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15 09:00'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 50);

        // One hour past the deadline, before the nightly sweep has run.
        $this->travelTo(Carbon::parse('2027-01-15 10:00'));

        try {
            $w->debit($vendor, 10);
            $this->fail('expected the debit to be refused');
        } catch (InsufficientCoinsException) {
        }

        $this->assertSame(0, CoinLedgerEntry::where('type', CoinLedgerTypeEnum::DEBIT->value)->count());

        $this->assertSame(0, $w->balance($vendor));
        $this->assertSame(50, -CoinLedgerEntry::where('type', CoinLedgerTypeEnum::EXPIRY->value)->sum('amount'));
    }

    public function test_a_partly_expired_lot_still_pays_for_what_it_can(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 40);

        $this->travelTo(Carbon::parse('2026-09-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 30);

        // January's lot is past its date; September's is not.
        $this->travelTo(Carbon::parse('2027-02-01'));
        $entries = $w->debit($vendor, 25);

        $this->assertSame(-25, $entries->sum('amount'));
        $this->assertSame(5, $w->balance($vendor));
        $this->assertSame(40, -CoinLedgerEntry::where('type', CoinLedgerTypeEnum::EXPIRY->value)->sum('amount'));
    }

    public function test_a_fully_expired_vendor_drops_out_of_the_nightly_sweep(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 50);

        $this->travelTo(Carbon::parse('2027-02-01'));
        $this->assertTrue($this->ledger()->vendorsWithExpiredLots(now())->contains($vendor->id));

        $w->expire($vendor);

        // Otherwise the sweep revisits this vendor every night, forever.
        $this->assertFalse($this->ledger()->vendorsWithExpiredLots(now())->contains($vendor->id));
    }

    public function test_ledger_history_outlives_an_attempt_to_delete_the_vendor(): void
    {
        $vendor = $this->vendor();
        $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 50);

        // A cascade would bypass the model guard and erase committed history.
        $this->expectException(QueryException::class);

        try {
            DB::table('users')->where('id', $vendor->id)->delete();
        } finally {
            $this->assertSame(1, CoinLedgerEntry::count());
        }
    }

    public function test_the_scheduled_job_expires_every_affected_vendor(): void
    {
        $w = $this->wallets();
        $due = $this->vendor();
        $fresh = $this->vendor();

        $this->travelTo(Carbon::parse('2026-01-15'));
        $w->credit($due, CoinLedgerTypeEnum::PURCHASE, 60);

        $this->travelTo(Carbon::parse('2027-01-10'));
        $w->credit($fresh, CoinLedgerTypeEnum::PURCHASE, 60);

        $this->travelTo(Carbon::parse('2027-02-01'));
        (new ExpireCoins)->handle($this->ledger(), $w);

        $this->assertSame(0, $w->balance($due));
        $this->assertSame(60, $w->balance($fresh));
    }

    public function test_the_expiry_job_is_on_the_schedule(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('ExpireCoins');
    }

    // ==================== Refunds ====================

    public function test_a_refund_returns_coins_on_their_original_deadline(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15 09:00'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);
        $lead = $this->lead($vendor);

        // Refunded eleven months later — the coins must not gain a fresh year.
        $this->travelTo(Carbon::parse('2026-12-15 09:00'));
        $w->debit($vendor, 10, $lead);
        $credits = $w->refund($vendor, $lead);

        $this->assertCount(1, $credits);
        $this->assertSame(100, $w->balance($vendor));
        $this->assertTrue($credits->sole()->expires_at?->equalTo(Carbon::parse('2027-01-15 09:00')));

        // And it really does die on the original date rather than a year out.
        $this->travelTo(Carbon::parse('2027-01-16'));
        $this->assertSame(100, $w->expire($vendor));
        $this->assertSame(0, $w->balance($vendor));
    }

    public function test_a_refund_spanning_two_lots_keeps_each_deadline(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 10);

        $this->travelTo(Carbon::parse('2026-06-15'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 10);

        $lead = $this->lead($vendor);
        $w->debit($vendor, 15, $lead);

        $deadlines = $w->refund($vendor, $lead)->pluck('expires_at')->map(fn ($d) => $d?->toDateString())->all();

        $this->assertSame(['2027-01-15', '2027-06-15'], $deadlines);
        $this->assertSame(20, $w->balance($vendor));
    }

    public function test_refunding_an_already_expired_lot_leaves_no_spendable_value(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-01-15 09:00'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 50);

        $lead = $this->lead($vendor);
        $w->debit($vendor, 10, $lead);

        // The lot's deadline passes before anyone gets round to the refund.
        $this->travelTo(Carbon::parse('2027-02-01'));
        $credits = $w->refund($vendor, $lead);

        // The credit is still recorded for audit, but it must not inflate the balance.
        $this->assertCount(1, $credits);
        $this->assertSame(0, $w->balance($vendor));
        $this->assertSame(0, $this->ledger()->replayBalance($vendor->id));
    }

    public function test_a_reference_is_only_refunded_once(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);

        $lead = $this->lead($vendor);
        $w->debit($vendor, 10, $lead);

        $this->assertCount(1, $w->refund($vendor, $lead));
        $this->assertCount(0, $w->refund($vendor, $lead));
        $this->assertSame(100, $w->balance($vendor));
    }

    private function lead(User $vendor): BillableLead
    {
        return BillableLead::create([
            'vendor_id' => $vendor->id,
            'buyer_identity' => self::BUYER,
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => 0,
            'state' => BillableLeadStateEnum::UNBILLED,
            'window_start' => now(),
        ]);
    }

    // ==================== Reporting ====================

    public function test_the_report_separates_coins_sold_from_coins_redeemed(): void
    {
        $w = $this->wallets();
        $a = $this->vendor();
        $b = $this->vendor();

        $this->travelTo(Carbon::parse('2026-03-01'));
        $w->credit($a, CoinLedgerTypeEnum::PURCHASE, 100);
        $w->credit($b, CoinLedgerTypeEnum::PURCHASE, 50);
        $w->credit($b, CoinLedgerTypeEnum::BONUS, 10);

        $this->travelTo(Carbon::parse('2026-03-15'));
        $w->debit($a, 30);
        $w->debit($b, 20);

        $this->travelTo(Carbon::parse('2026-04-10'));
        $w->credit($a, CoinLedgerTypeEnum::PURCHASE, 40);

        $march = $this->ledger()->report(Carbon::parse('2026-03-01'), Carbon::parse('2026-03-31 23:59:59'));

        $this->assertSame(150, $march['coins_sold']);
        $this->assertSame(10, $march['coins_granted']);
        $this->assertSame(0, $march['coins_refunded']);
        $this->assertSame(50, $march['coins_redeemed']);
        $this->assertSame(0, $march['coins_expired']);
        $this->assertSame(110, $march['outstanding_liability']);

        $april = $this->ledger()->report(Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30 23:59:59'));

        $this->assertSame(40, $april['coins_sold']);
        $this->assertSame(0, $april['coins_redeemed']);
        $this->assertSame(150, $april['outstanding_liability']);
    }

    public function test_a_refund_is_reported_as_a_refund_not_a_grant(): void
    {
        $vendor = $this->vendor();
        $w = $this->wallets();

        $this->travelTo(Carbon::parse('2026-05-01'));
        $w->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 100);
        $w->credit($vendor, CoinLedgerTypeEnum::BONUS, 20);

        $lead = $this->lead($vendor);
        $w->debit($vendor, 30, $lead);
        $w->refund($vendor, $lead);

        $report = $this->ledger()->report(Carbon::parse('2026-05-01'), Carbon::parse('2026-05-31 23:59:59'));

        // Returning a vendor's own coins is not the platform granting new ones.
        $this->assertSame(20, $report['coins_granted']);
        $this->assertSame(30, $report['coins_refunded']);
        $this->assertSame(30, $report['coins_redeemed']);
        $this->assertSame(100, $report['coins_sold']);
        $this->assertSame(120, $report['outstanding_liability']);
    }

    // ==================== Paying for leads ====================

    public function test_a_lead_is_charged_from_the_wallet_when_the_vendor_can_afford_it(): void
    {
        $vendor = $this->vendor();
        $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 25);

        $lead = $this->billLead($vendor);

        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead?->state);
        $this->assertSame(10, $lead?->coins_charged);
        $this->assertSame(15, $this->wallets()->balance($vendor));

        $debit = CoinLedgerEntry::where('type', CoinLedgerTypeEnum::DEBIT->value)->sole();
        $this->assertTrue($debit->reference?->is($lead));
    }

    public function test_a_vendor_who_cannot_cover_the_price_is_not_charged_at_all(): void
    {
        $vendor = $this->vendor();
        $this->wallets()->credit($vendor, CoinLedgerTypeEnum::PURCHASE, 5);

        $lead = $this->billLead($vendor);

        $this->assertSame(BillableLeadStateEnum::UNBILLED, $lead?->state);
        $this->assertSame(0, $lead?->coins_charged);
        $this->assertSame(5, $this->wallets()->balance($vendor));
        $this->assertSame(0, CoinLedgerEntry::where('type', CoinLedgerTypeEnum::DEBIT->value)->count());
    }

    public function test_a_vendor_with_no_wallet_at_all_gets_an_unbilled_lead(): void
    {
        $vendor = $this->vendor();

        $lead = $this->billLead($vendor);

        $this->assertSame(BillableLeadStateEnum::UNBILLED, $lead?->state);
        $this->assertSame(0, $this->wallets()->balance($vendor));
        $this->assertSame(0, CoinLedgerEntry::where('vendor_id', $vendor->id)->count());
    }

    private function billLead(User $vendor): ?BillableLead
    {
        Event::fake([VendorContactClicked::class]);

        $links = app(ContactLinkService::class);
        $click = $links->recordClick($links->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER));

        return app(LeadBillingService::class)->bill($click);
    }
}
