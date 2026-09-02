<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum NotificationChannelEnum: string
{
    use EnumToArray;
    case WHATSAPP = 'whatsapp';
    case EMAIL = 'email';
    case IN_APP = 'in_app';
}
