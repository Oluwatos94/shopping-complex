<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Payments;

use RuntimeException;

/**
 * Paystack could not be reached or answered with a server error, so the outcome
 * is unknown — the transaction may well exist on their side. Callers must treat
 * this as retryable: never acknowledge the webhook, never discard a pending
 * purchase. Distinct from a plain RuntimeException, which means Paystack gave a
 * definitive answer (rejected, not successful, wrong currency, tampered).
 */
final class PaystackUnavailableException extends RuntimeException {}
