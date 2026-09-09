<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Billing\Models\ContactClick;

/**
 * A buyer opened a vendor's contact link. Billing listeners debit from this;
 * they must honour {@see ContactClick::$is_billable}, which is false once the
 * link has outlived its signature.
 */
class VendorContactClicked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ContactClick $click,
    ) {}
}
