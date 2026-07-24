<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Reviews\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum ReviewStatusEnum: string
{
    use EnumToArray;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
