<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Contracts\LeadDebitor;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Listeners\RecordBillableLead;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Billing\Models\ContactClick;
use ModulesShoppingComplex\Billing\Services\ContactLinkService;
use ModulesShoppingComplex\Billing\Services\LeadBillingService;
use ModulesShoppingComplex\Billing\Services\NullLeadDebitor;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class BillableLeadTest extends TestCase
{
    use RefreshDatabase;

    private const BUYER = '2348011112222';

    private SpyLeadDebitor $debitor;

    protected function setUp(): void
    {
        parent::setUp();

        config(['billing.leads.coin_cost' => 10]);

        $this->debitor = new SpyLeadDebitor;
        $this->app->instance(LeadDebitor::class, $this->debitor);
    }

    private function vendor(): User
    {
        return User::factory()->create(['role' => 'vendor', 'whatsapp_number' => '08031234567']);
    }

    private function click(User $vendor, ?string $buyer = self::BUYER): ContactClick
    {
        $links = app(ContactLinkService::class);
        $link = $links->mint($vendor, ViewSourceEnum::WHATSAPP, $buyer);

        $click = $links->recordClick($link);
        $this->assertNotNull($click);

        return $click;
    }

    private function withoutBillingListener(): void
    {
        Event::fake([VendorContactClicked::class]);
    }

    private function billing(): LeadBillingService
    {
        return app(LeadBillingService::class);
    }

    // ==================== First contact ====================

    public function test_the_first_click_for_a_pair_opens_a_charged_lead(): void
    {
        $this->withoutBillingListener();
        $vendor = $this->vendor();
        $click = $this->click($vendor);

        $lead = $this->billing()->bill($click);

        $this->assertNotNull($lead);
        $this->assertSame(BillableLeadStateEnum::CHARGED, $lead->state);
        $this->assertSame(10, $lead->coins_charged);
        $this->assertSame(self::BUYER, $lead->buyer_identity);
        $this->assertSame($vendor->id, $lead->vendor_id);
        $this->assertSame($click->id, $lead->contact_click_id);
        $this->assertSame(0, $lead->repeat_count);
        $this->assertSame([[$vendor->id, 10]], $this->debitor->debits);
    }

    // ==================== Dedupe inside the window ====================

    public function test_two_clicks_one_minute_apart_produce_one_charge(): void
    {
        $this->withoutBillingListener();
        $vendor = $this->vendor();

        $this->billing()->bill($this->click($vendor));
        $this->travel(1)->minute();
        $second = $this->click($vendor);
        $this->billing()->bill($second);

        $this->assertSame(1, BillableLead::count());
        $this->assertCount(1, $this->debitor->debits);

        $lead = BillableLead::firstOrFail();
        $this->assertSame(1, $lead->repeat_count);
        $this->assertFalse($second->fresh()?->is_billable);
    }

    public function test_the_same_pair_31_days_later_is_a_new_lead(): void
    {
        $this->withoutBillingListener();
        $vendor = $this->vendor();

        $this->billing()->bill($this->click($vendor));
        $this->travel(31)->days();
        $this->billing()->bill($this->click($vendor));

        $this->assertSame(2, BillableLead::count());
        $this->assertCount(2, $this->debitor->debits);
    }

    public function test_a_different_buyer_or_vendor_is_a_separate_lead(): void
    {
        $this->withoutBillingListener();
        $vendor = $this->vendor();
        $other = $this->vendor();

        $this->billing()->bill($this->click($vendor));
        $this->billing()->bill($this->click($vendor, '2348099998888'));
        $this->billing()->bill($this->click($other));

        $this->assertSame(3, BillableLead::count());
        $this->assertCount(3, $this->debitor->debits);
    }

    // ==================== Clicks that never bill ====================

    public function test_an_expired_link_click_is_not_billed(): void
    {
        $this->withoutBillingListener();
        $vendor = $this->vendor();
        $link = app(ContactLinkService::class)->mint($vendor, ViewSourceEnum::WHATSAPP, self::BUYER);

        $this->travel(ContactLinkService::TOKEN_TTL_DAYS + 1)->days();
        $click = app(ContactLinkService::class)->recordClick($link);

        $this->assertFalse($click?->is_billable);
        $this->assertNull($this->billing()->bill($click));
        $this->assertSame(0, BillableLead::count());
        $this->assertSame([], $this->debitor->debits);
    }

    public function test_a_click_without_a_buyer_identity_is_not_billed(): void
    {
        $this->withoutBillingListener();

        $this->assertNull($this->billing()->bill($this->click($this->vendor(), buyer: null)));
        $this->assertSame(0, BillableLead::count());
    }

    // ==================== Atomicity and races ====================

    public function test_a_failed_debit_rolls_the_lead_back(): void
    {
        $this->withoutBillingListener();
        $click = $this->click($this->vendor());

        $this->app->instance(LeadDebitor::class, new class implements LeadDebitor
        {
            public function debit(User $vendor, BillableLead $lead, int $coins): int
            {
                throw new \RuntimeException('wallet unavailable');
            }
        });

        try {
            app(LeadBillingService::class)->bill($click);
            $this->fail('expected the debit failure to propagate');
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, BillableLead::count());
        $this->assertTrue($click->fresh()?->is_billable);
    }

    public function test_without_a_wallet_the_lead_is_unbilled_but_still_deduplicated(): void
    {
        $this->withoutBillingListener();
        $this->app->bind(LeadDebitor::class, NullLeadDebitor::class);
        $vendor = $this->vendor();

        $lead = app(LeadBillingService::class)->bill($this->click($vendor));

        $this->assertSame(BillableLeadStateEnum::UNBILLED, $lead?->state);
        $this->assertSame(0, $lead?->coins_charged);

        // The introduction still happened, so the pair is not charged when the wallet arrives.
        $second = $this->click($vendor);
        app(LeadBillingService::class)->bill($second);

        $this->assertSame(1, BillableLead::count());
        $this->assertFalse($second->fresh()?->is_billable);
    }

    public function test_redelivering_the_opening_click_is_idempotent(): void
    {
        $this->withoutBillingListener();
        $click = $this->click($this->vendor());

        $this->billing()->bill($click);
        $this->billing()->bill($click);

        $this->assertSame(0, BillableLead::firstOrFail()->repeat_count);
        $this->assertCount(1, $this->debitor->debits);
    }

    public function test_the_database_refuses_a_duplicate_pair_for_the_same_window(): void
    {
        $row = [
            'vendor_id' => $this->vendor()->id,
            'buyer_identity' => self::BUYER,
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => 10,
            'state' => BillableLeadStateEnum::CHARGED,
            'window_start' => now(),
        ];

        BillableLead::create($row);

        $this->expectException(QueryException::class);
        BillableLead::create($row);
    }

    // ==================== Wiring ====================

    public function test_the_click_event_queues_the_billing_listener(): void
    {
        Queue::fake();

        $this->click($this->vendor());

        Queue::assertPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $job) => $job->class === RecordBillableLead::class,
        );
    }

    public function test_two_redirect_hits_end_to_end_charge_once(): void
    {
        $vendor = $this->vendor();
        $links = app(ContactLinkService::class);

        $this->get((string) $links->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER));
        $this->travel(2)->hours();
        $this->get((string) $links->urlFor($vendor, ViewSourceEnum::WHATSAPP, self::BUYER));

        $this->assertSame(2, ContactClick::count());
        $this->assertSame(1, BillableLead::count());
        $this->assertCount(1, $this->debitor->debits);
    }
}

final class SpyLeadDebitor implements LeadDebitor
{
    /** @var array<int, array{0: int, 1: int}> */
    public array $debits = [];

    public function debit(User $vendor, BillableLead $lead, int $coins): int
    {
        $this->debits[] = [$vendor->id, $coins];

        return $coins;
    }
}
