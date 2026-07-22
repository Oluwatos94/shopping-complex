<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments;

/**
 * How a provider's checkout hands control to the vendor.
 *
 * - REDIRECT: leave the SPA and send the vendor to an external page (e.g. Paystack).
 * - INTERACTIVE: embed a hosted flow in-page (e.g. the SEP-24 anchor deposit UI).
 */
enum CheckoutTypeEnum: string
{
    case REDIRECT = 'redirect';
    case INTERACTIVE = 'interactive';
}
