<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum AnchorReconciliationEnum: string
{
    use EnumToArray;

    case AMOUNT_MISMATCH = 'amount_mismatch';

    case RESOLVED = 'resolved';
}
