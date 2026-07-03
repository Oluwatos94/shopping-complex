<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum AnchorReconciliationEnum: string
{
    use EnumToArray;

    case AMOUNT_MISMATCH = 'amount_mismatch';

    case RESOLVED = 'resolved';
}
