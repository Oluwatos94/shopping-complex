<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use ModulesShoppingComplex\Billing\Events\VendorContactClicked;
use ModulesShoppingComplex\Billing\Services\LeadBillingService;

class RecordBillableLead implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly LeadBillingService $billing,
    ) {}

    public function handle(VendorContactClicked $event): void
    {
        $this->billing->bill($event->click);
    }
}
