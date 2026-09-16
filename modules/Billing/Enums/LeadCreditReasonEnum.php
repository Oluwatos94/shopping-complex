<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum LeadCreditReasonEnum: string
{
    use EnumToArray;

    case CONTACT_NOT_SENT = 'contact_not_sent';
    case INVALID_NUMBER = 'invalid_number';
    case DUPLICATE = 'duplicate';
    case FLAGGED = 'flagged';
}
