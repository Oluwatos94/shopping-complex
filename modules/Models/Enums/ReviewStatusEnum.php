<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

enum ReviewStatusEnum: string
{
    use EnumToArray;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
