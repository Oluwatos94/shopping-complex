<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Models\User;

/**
 * A recurring auto-renew charge failed — the subscription will lapse
 * unless the vendor intervenes.
 */
class SubscriptionRenewalFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $vendor,
    ) {}
}
