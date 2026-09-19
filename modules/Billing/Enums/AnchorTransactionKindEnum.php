<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Billing\Models\AnchorTransaction;
use ModulesShoppingComplex\Shared\Support\EnumToArray;

/**
 * The kind of anchor interaction an {@see AnchorTransaction} records:
 * a subscription SEP-24 deposit, a coin-pack SEP-24 deposit, or a recurring MPP charge.
 */
enum AnchorTransactionKindEnum: string
{
    use EnumToArray;

    case DEPOSIT = 'deposit';
    case COIN_DEPOSIT = 'coin_deposit';
    case MPP_CHARGE = 'mpp_charge';
}
