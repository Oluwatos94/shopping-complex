<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum WhatsAppSessionStateEnum: string
{
    use EnumToArray;

    case IDLE = 'idle';
    case AWAITING_LOCATION = 'awaiting_location';
    case SHOWING_VENDORS = 'showing_vendors';
    case SHOWING_PRODUCTS = 'showing_products';
}
