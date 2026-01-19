<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum UserEnum: string
{
    use EnumToArray;
    case ADMIN = 'admin';
    case CUSTOMER = 'customer';
    case VENDOR = 'vendor';
}
