<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum SupportConversationStatusEnum: string
{
    use EnumToArray;

    case BOT = 'bot';
    case AWAITING_AGENT = 'awaiting_agent';
    case WITH_AGENT = 'with_agent';
    case RESOLVED = 'resolved';
}
