<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum WhatsAppSessionStateEnum: string
{
    use EnumToArray;

    case IDLE = 'idle';
    case AWAITING_LOCATION = 'awaiting_location';
    case AWAITING_EXPAND_CHOICE = 'awaiting_expand_choice';
    case SHOWING_VENDORS = 'showing_vendors';
    case SHOWING_PRODUCTS = 'showing_products';
}
