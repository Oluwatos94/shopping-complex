<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\User;

/**
 * A subscription payment settled — either an initial/manual checkout
 * or a recurring auto-renew charge.
 */
class SubscriptionPaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $vendor,
        public readonly float $amount,
        public readonly PaymentMethodEnum $method,
        public readonly bool $isRenewal = false,
    ) {}
}
