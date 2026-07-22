<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments\Stellar;

/**
 * A recurring MPP charge could not be settled on-chain — insufficient balance, a frozen/revoked
 * trustline, or a Soroban/transport failure. The renewal job treats this as a non-fatal per-vendor
 * failure: the vendor is skipped (not extended) and lapses through the normal expiry path.
 */
final class ChargeFailedException extends \RuntimeException {}
