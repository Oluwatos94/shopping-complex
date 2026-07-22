<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum ViewSourceEnum: string
{
    use EnumToArray;

    case WEB = 'web';
    case WHATSAPP = 'whatsapp';
}
