<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum BillableLeadStateEnum: string
{
    use EnumToArray;

    case CHARGED = 'charged';
}
