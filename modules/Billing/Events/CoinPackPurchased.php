<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Billing\Models\CoinPurchase;

/**
 * A coin pack was paid for and the coins were credited. Fired once per purchase,
 * after the crediting transaction commits.
 */
class CoinPackPurchased
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CoinPurchase $purchase,
        public readonly int $newBalance,
    ) {}
}
