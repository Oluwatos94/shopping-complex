<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum LeadUnbilledReasonEnum: string
{
    use EnumToArray;

    case INSUFFICIENT_BALANCE = 'insufficient_balance';
    case DAILY_CAP = 'daily_cap';
    case SELF_CLICK = 'self_click';
    case VELOCITY_BUYER = 'velocity_buyer';
    case VELOCITY_IP = 'velocity_ip';

    /**
     * Reasons that mark abuse worth a human's review, as opposed to a vendor's
     * own cap or an empty wallet.
     *
     * @return array<int, self>
     */
    public static function flagged(): array
    {
        return [self::SELF_CLICK, self::VELOCITY_BUYER, self::VELOCITY_IP];
    }
}
