<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments;

/**
 * A verified, successful payment, normalised across providers.
 *
 * A provider returns this only when the payment is confirmed; any failure
 * (unverifiable reference, wrong owner, unsuccessful charge) is signalled by
 * throwing a \RuntimeException instead.
 */
final readonly class PaymentResult
{
    public function __construct(
        public string $reference,
        public int $planId,
        public float $amountPaid,
    ) {}
}
