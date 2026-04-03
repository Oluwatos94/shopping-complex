<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum ViewSourceEnum: string
{
    use EnumToArray;

    case WEB = 'web';
    case WHATSAPP = 'whatsapp';
}
