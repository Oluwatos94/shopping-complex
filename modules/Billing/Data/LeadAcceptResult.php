<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Data;

use ModulesShoppingComplex\Billing\Enums\LeadAcceptStatusEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;

final readonly class LeadAcceptResult
{
    public function __construct(
        public LeadAcceptStatusEnum $status,
        public BillableLead $lead,
        public int $cost = 0,
        public int $balance = 0,
    ) {}
}
