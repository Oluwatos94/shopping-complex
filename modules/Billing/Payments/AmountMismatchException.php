<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments;

final class AmountMismatchException extends \RuntimeException
{
    public function __construct(
        public readonly float $expected,
        public readonly float $actual,
    ) {
        parent::__construct(sprintf(
            'Payment amount %.2f does not match the plan price %.2f.',
            $actual,
            $expected,
        ));
    }
}
