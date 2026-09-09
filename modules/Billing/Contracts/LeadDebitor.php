<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Contracts;

use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;

/**
 * Takes the coins for a lead. Called inside the same transaction as the lead
 * insert, so an implementation that throws rolls the lead back too. Returning
 * 0 leaves the lead recorded as unbilled — the coin wallet supplies the real
 * implementation; until then nothing is taken.
 */
interface LeadDebitor
{
    /**
     * @return int coins actually taken
     */
    public function debit(User $vendor, BillableLead $lead, int $coins): int;
}
