<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Support\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum SupportMessageRoleEnum: string
{
    use EnumToArray;

    case USER = 'user';
    case ASSISTANT = 'assistant';
    case AGENT = 'agent';
}
