<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Payments;

use ModulesShoppingComplex\Models\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Models\SubscriptionPlan;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Services\Payments\Contracts\PaymentProvider;
use ModulesShoppingComplex\Services\PaystackClient;

final readonly class PaystackProvider implements PaymentProvider
{
    public function __construct(private PaystackClient $client) {}

    public function method(): PaymentMethodEnum
    {
        return PaymentMethodEnum::PAYSTACK;
    }

    public function supportsRecurring(): bool
    {
        return false;
    }

    public function startCheckout(User $vendor, SubscriptionPlan $plan): CheckoutSession
    {
        // Amount must be in kobo (Paystack uses the smallest currency unit).
        $amountInKobo = (int) round($plan->price * 100);

        $authorizationUrl = $this->client->initializeTransaction(
            email: $vendor->email,
            amountInKobo: $amountInKobo,
            metadata: [
                'vendor_id' => $vendor->id,
                'plan_id' => $plan->id,
                'plan_slug' => $plan->slug,
            ],
            callbackUrl: route('vendor.subscription.callback'),
        );

        // Paystack mints the reference itself and returns it on the callback,
        // so we have no reference to surface up-front.
        return new CheckoutSession(CheckoutTypeEnum::REDIRECT, $authorizationUrl);
    }

    public function confirm(string $reference, User $vendor): PaymentResult
    {
        $data = $this->client->verifyTransaction($reference);

        $metadata = $data['metadata'] ?? [];
        $vendorId = (int) ($metadata['vendor_id'] ?? 0);
        $planId = (int) ($metadata['plan_id'] ?? 0);

        if (! $vendorId || ! $planId) {
            throw new \RuntimeException('Invalid payment metadata.');
        }

        if ($vendorId !== $vendor->id) {
            throw new \RuntimeException('Payment reference does not belong to your account.');
        }

        return new PaymentResult(
            reference: $reference,
            planId: $planId,
            amountPaid: $data['amount'] / 100, // kobo → naira
        );
    }
}
