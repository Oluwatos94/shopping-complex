<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Contracts;

use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;

/**
 * Takes the coins for a charged lead. Called inside the same transaction as
 * the lead insert, so an implementation that throws rolls the lead back too.
 * The coin wallet supplies the real implementation; until then it is a no-op.
 */
interface LeadDebitor
{
    public function debit(User $vendor, BillableLead $lead): void;
}
