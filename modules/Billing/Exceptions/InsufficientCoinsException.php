<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Exceptions;

use RuntimeException;

final class InsufficientCoinsException extends RuntimeException
{
    public function __construct(
        public readonly int $balance,
        public readonly int $requested,
    ) {
        parent::__construct(sprintf('Insufficient coins: balance %d, requested %d.', $balance, $requested));
    }
}
