<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments\Contracts;

use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Services\Payments\CheckoutSession;
use ModulesShoppingComplex\Services\Payments\PaymentResult;

/**
 * A swappable payment rail for vendor subscriptions.
 *
 * Implementations own only the transport + verification for their gateway; the
 * subscription lifecycle (activation, idempotency, expiry) stays in
 * {@see \ModulesShoppingComplex\Services\SubscriptionService}. Adding a new rail
 * is one implementation of this contract plus one {@see PaymentMethodEnum} case.
 */
interface PaymentProvider
{
    /** The method this provider handles; its key in the PaymentProviderManager. */
    public function method(): PaymentMethodEnum;

    /** Whether this rail can settle automatic recurring renewals (e.g. Stellar MPP). */
    public function supportsRecurring(): bool;

    /**
     * Begin a payment for $plan and return where to send $vendor to pay.
     *
     * @throws \RuntimeException if the gateway cannot start the checkout
     */
    public function startCheckout(User $vendor, SubscriptionPlan $plan): CheckoutSession;

    /**
     * Verify a completed payment by its $reference and return the normalised result.
     *
     * Implementations MUST validate that the payment belongs to $vendor and was
     * successful, throwing \RuntimeException otherwise.
     *
     * @throws \RuntimeException if the payment is unverifiable, unsuccessful, or not the vendor's
     */
    public function confirm(string $reference, User $vendor): PaymentResult;
}
