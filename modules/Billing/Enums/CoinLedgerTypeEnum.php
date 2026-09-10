<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Enums;

use ModulesShoppingComplex\Shared\Support\EnumToArray;

enum CoinLedgerTypeEnum: string
{
    use EnumToArray;

    case PURCHASE = 'purchase';
    case BONUS = 'bonus';
    case DEBIT = 'debit';
    case CREDIT = 'credit';
    case EXPIRY = 'expiry';
    case PROMO = 'promo';

    public function addsCoins(): bool
    {
        return match ($this) {
            self::PURCHASE, self::BONUS, self::CREDIT, self::PROMO => true,
            self::DEBIT, self::EXPIRY => false,
        };
    }
}
