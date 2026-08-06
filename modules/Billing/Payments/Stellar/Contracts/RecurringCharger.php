<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments\Stellar\Contracts;

use ModulesShoppingComplex\Billing\Models\StellarWallet;
use ModulesShoppingComplex\Billing\Payments\Stellar\ChargeFailedException;
use ModulesShoppingComplex\Billing\Payments\Stellar\SorobanCharger;

/**
 * Executes a single recurring subscription charge on-chain: moves $amount NGNC from a vendor's
 * custodial wallet to Jiidaa's platform wallet and returns the settled Stellar transaction hash.
 *
 * The concrete rail is swappable: {@see SorobanCharger}
 * invokes the NGNC SAC `transfer` directly via the custodial key.
 */
interface RecurringCharger
{
    /**
     * Charge $amount NGNC from the vendor's custodial wallet to the platform wallet.
     *
     * @return string the settled on-chain transaction hash (hex)
     *
     * @throws ChargeFailedException on any failure
     */
    public function charge(StellarWallet $from, float $amount): string;
}
