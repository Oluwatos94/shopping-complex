<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Billing\Models\BillableLead;

class VendorLeadRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BillableLead $lead,
    ) {}
}
