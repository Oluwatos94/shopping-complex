<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Analytics\Enums\ViewSourceEnum;
use ModulesShoppingComplex\Billing\Enums\BillableLeadStateEnum;
use ModulesShoppingComplex\Billing\Enums\LeadCreditReasonEnum;
use ModulesShoppingComplex\Billing\Enums\LeadUnbilledReasonEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class VendorLeadHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): User
    {
        return User::factory()->create(['role' => 'vendor']);
    }

    private function lead(User $vendor, array $attributes = []): BillableLead
    {
        static $seq = 0;
        $seq++;

        return BillableLead::create(array_merge([
            'vendor_id' => $vendor->id,
            'buyer_identity' => 'buyer_'.$seq,
            'channel' => ViewSourceEnum::WHATSAPP,
            'coins_charged' => 10,
            'state' => BillableLeadStateEnum::CHARGED,
            'window_start' => now()->subMinutes($seq),
        ], $attributes));
    }

    public function test_the_history_lists_leads_with_their_state(): void
    {
        $vendor = $this->vendor();
        $this->lead($vendor, ['buyer_search' => 'ankara gown', 'buyer_area' => 'Yaba, Lagos']);
        $this->lead($vendor, ['state' => BillableLeadStateEnum::UNBILLED, 'coins_charged' => 0, 'unbilled_reason' => LeadUnbilledReasonEnum::INSUFFICIENT_BALANCE]);
        $this->lead($vendor, ['credit_reason' => LeadCreditReasonEnum::DUPLICATE]);

        $this->actingAs($vendor)->get('/vendor/leads')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Vendor/Leads', false)
                ->has('leads.data', 3)
                ->where('leads.data.0.state', 'credited')
                ->where('leads.data.1.state', 'unbilled')
                ->where('leads.data.2.state', 'billed')
                ->where('leads.data.2.search', 'ankara gown')
                ->where('leads.data.2.channel', 'bot')
        );
    }

    public function test_it_filters_by_state(): void
    {
        $vendor = $this->vendor();
        $this->lead($vendor);
        $this->lead($vendor, ['state' => BillableLeadStateEnum::UNBILLED, 'coins_charged' => 0, 'unbilled_reason' => LeadUnbilledReasonEnum::INSUFFICIENT_BALANCE]);

        $this->actingAs($vendor)->get('/vendor/leads?state=unbilled')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('leads.data', 1)
                ->where('leads.data.0.state', 'unbilled')
        );
    }

    public function test_every_charged_lead_is_visible_for_reconciliation(): void
    {
        $vendor = $this->vendor();
        $this->lead($vendor);
        $this->lead($vendor);
        $this->lead($vendor, ['state' => BillableLeadStateEnum::UNBILLED, 'coins_charged' => 0]);

        $this->actingAs($vendor)->get('/vendor/leads?state=billed')->assertInertia(
            fn (AssertableInertia $page) => $page->has('leads.data', 2)
        );
    }

    public function test_it_exports_a_csv(): void
    {
        $vendor = $this->vendor();
        $this->lead($vendor, ['buyer_search' => 'phone repair', 'buyer_area' => 'Ikeja, Lagos']);

        $response = $this->actingAs($vendor)->get('/vendor/leads/export');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $body = $response->streamedContent();
        $this->assertStringContainsString('Searched for', $body);
        $this->assertStringContainsString('phone repair', $body);
    }

    public function test_the_csv_export_neutralises_formula_injection(): void
    {
        $vendor = $this->vendor();
        $this->lead($vendor, ['buyer_search' => '=HYPERLINK("http://evil")']);

        $body = $this->actingAs($vendor)->get('/vendor/leads/export')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $body);
        $this->assertStringNotContainsString(',=HYPERLINK', $body);
    }

    public function test_an_invalid_date_filter_is_rejected_not_fatal(): void
    {
        $vendor = $this->vendor();

        $this->actingAs($vendor)->get('/vendor/leads?from=not-a-date')->assertSessionHasErrors('from');
    }

    public function test_a_non_vendor_cannot_view_leads(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->get('/vendor/leads')->assertRedirect(route('home'));
    }
}
