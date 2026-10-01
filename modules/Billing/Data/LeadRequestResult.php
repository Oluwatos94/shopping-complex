<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Data;

use ModulesShoppingComplex\Billing\Enums\LeadRequestStatusEnum;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;

final readonly class LeadRequestResult
{
    public function __construct(
        public LeadRequestStatusEnum $status,
        public ?BillableLead $lead = null,
        public ?User $vendor = null,
    ) {}
}
