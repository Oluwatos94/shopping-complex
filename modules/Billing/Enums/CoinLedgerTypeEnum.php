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

    /**
     * Coins that may be handed out directly. CREDIT is excluded because it
     * means "refund", and a refund only exists against the charge it reverses.
     */
    public function isDirectGrant(): bool
    {
        return match ($this) {
            self::PURCHASE, self::BONUS, self::PROMO => true,
            self::CREDIT, self::DEBIT, self::EXPIRY => false,
        };
    }
}
