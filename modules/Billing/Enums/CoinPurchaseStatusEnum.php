<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum CoinPurchaseStatusEnum: string
{
    use EnumToArray;

    case PENDING = 'pending';
    case COMPLETED = 'completed';
}
