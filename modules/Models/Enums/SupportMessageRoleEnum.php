<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum SupportMessageRoleEnum: string
{
    use EnumToArray;

    case USER = 'user';
    case ASSISTANT = 'assistant';
    case AGENT = 'agent';
}
