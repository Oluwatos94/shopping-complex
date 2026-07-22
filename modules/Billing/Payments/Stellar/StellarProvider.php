<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments\Stellar;

use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Models\SubscriptionPlan;
use ModulesShoppingComplex\Billing\Payments\CheckoutSession;
use ModulesShoppingComplex\Billing\Payments\Contracts\PaymentProvider;
use ModulesShoppingComplex\Billing\Payments\PaymentResult;
use ModulesShoppingComplex\Models\User;

final readonly class StellarProvider implements PaymentProvider
{
    public function __construct(private StellarDepositService $deposits) {}

    public function method(): PaymentMethodEnum
    {
        return PaymentMethodEnum::STELLAR;
    }

    public function supportsRecurring(): bool
    {
        return true;
    }

    public function startCheckout(User $vendor, SubscriptionPlan $plan): CheckoutSession
    {
        return $this->deposits->start($vendor, $plan);
    }

    /**
     * Confirm the deposit settled. Re-syncs the SEP-24 status with the anchor (cheap once
     * already completed) and returns the settled amount + the plan it funded.
     *
     * @throws \RuntimeException if the deposit is unknown, not the vendor's, or incomplete
     */
    public function confirm(string $reference, User $vendor): PaymentResult
    {
        $transaction = $this->deposits->syncStatus($vendor, $reference);

        if (! $transaction->isCompleted()) {
            throw new \RuntimeException('The deposit has not completed yet.');
        }

        if ($transaction->plan_id === null) {
            throw new \RuntimeException('The deposit is not linked to a plan.');
        }

        return new PaymentResult(
            reference: $reference,
            planId: $transaction->plan_id,
            amountPaid: (float) $transaction->amount,
        );
    }
}
