<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments;

use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Billing\Enums\PaymentMethodEnum;
use ModulesShoppingComplex\Billing\Payments\Contracts\PaymentProvider;

/**
 * Resolves the {@see PaymentProvider} for a given {@see PaymentMethodEnum}.
 *
 * Providers are registered once (in AppServiceProvider) and indexed by their
 * own `method()`, so adding a rail never touches this class.
 */
final class PaymentProviderManager
{
    /** @var array<string, PaymentProvider> */
    private array $providers = [];

    /**
     * @param  iterable<PaymentProvider>  $providers
     */
    public function __construct(iterable $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->method()->value] = $provider;
        }
    }

    /**
     * @throws \RuntimeException if no provider is registered for the method (user-safe message;
     *                           the internal detail is logged, not surfaced)
     */
    public function for(PaymentMethodEnum $method): PaymentProvider
    {
        $provider = $this->providers[$method->value] ?? null;

        if ($provider === null) {
            // A missing provider is a server-side misconfiguration, not vendor error — log the
            // real detail for ops, but never reflect internal wiring back to the user.
            Log::error('No payment provider registered', ['method' => $method->value]);
            throw new \RuntimeException('The selected payment method is currently unavailable.');
        }

        return $provider;
    }
}
