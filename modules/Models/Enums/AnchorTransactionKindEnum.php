<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Models\Enums;

use App\Traits\EnumToArray;

/**
 * The kind of anchor interaction an {@see \ModulesShoppingComplex\Models\AnchorTransaction} records:
 * a SEP-24 deposit, or a recurring MPP charge.
 */
enum AnchorTransactionKindEnum: string
{
    use EnumToArray;

    case DEPOSIT = 'deposit';
    case MPP_CHARGE = 'mpp_charge';
}
