<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum PaymentMethodEnum: string
{
    use EnumToArray;

    case PAYSTACK = 'paystack';
    case STELLAR = 'stellar';
}
