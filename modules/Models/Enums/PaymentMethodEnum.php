<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum PaymentMethodEnum: string
{
    use EnumToArray;

    case PAYSTACK = 'paystack';
    case STELLAR = 'stellar';
}
