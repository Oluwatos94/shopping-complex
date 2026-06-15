<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments;

/**
 * The result of starting a checkout: where to send the vendor to pay.
 *
 * `url` is either an external redirect target (REDIRECT) or an embeddable
 * hosted-UI URL (INTERACTIVE). `reference` is the provider's payment id when it
 * is known up-front; for providers that only mint the reference later (Paystack
 * supplies it on the callback) it stays null.
 */
final readonly class CheckoutSession
{
    public function __construct(
        public CheckoutTypeEnum $type,
        public string $url,
        public ?string $reference = null,
    ) {}
}
