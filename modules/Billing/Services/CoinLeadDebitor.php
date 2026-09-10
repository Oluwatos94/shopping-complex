<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use ModulesShoppingComplex\Billing\Contracts\LeadDebitor;
use ModulesShoppingComplex\Billing\Exceptions\InsufficientCoinsException;
use ModulesShoppingComplex\Billing\Models\BillableLead;
use ModulesShoppingComplex\Identity\Models\User;

final class CoinLeadDebitor implements LeadDebitor
{
    public function __construct(
        private readonly CoinWalletService $wallet,
    ) {}

    public function debit(User $vendor, BillableLead $lead, int $coins): int
    {
        try {
            $this->wallet->debit($vendor, $coins, $lead);
        } catch (InsufficientCoinsException) {
            return 0;
        }

        return $coins;
    }
}
