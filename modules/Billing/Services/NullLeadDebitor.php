<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use ModulesShoppingComplex\Billing\Contracts\LeadDebitor;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;

final class NullLeadDebitor implements LeadDebitor
{
    public function debit(User $vendor, BillableLead $lead): void {}
}
