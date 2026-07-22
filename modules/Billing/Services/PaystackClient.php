<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Billing\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for the Paystack API.
 * Handles only transport concerns — no business logic.
 */
final class PaystackClient
{
    private const INITIALIZE_URL = 'https://api.paystack.co/transaction/initialize';

    private const VERIFY_URL = 'https://api.paystack.co/transaction/verify/';

    private const TIMEOUT_SECONDS = 15;

    public function __construct(private readonly string $secretKey) {}

    /**
     * Initialize a Paystack transaction and return the authorization URL.
     *
     * @param  array<string, mixed>  $metadata
     *
     * @throws \RuntimeException
     */
    public function initializeTransaction(
        string $email,
        int $amountInKobo,
        array $metadata,
        string $callbackUrl
    ): string {
        $response = Http::withToken($this->secretKey)
            ->timeout(self::TIMEOUT_SECONDS)
            ->post(self::INITIALIZE_URL, [
                'email' => $email,
                'amount' => $amountInKobo,
                'metadata' => $metadata,
                'callback_url' => $callbackUrl,
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::error('Paystack initialization failed', ['response' => $response->json()]);
            throw new \RuntimeException('Payment initialization failed. Please try again.');
        }

        $url = (string) $response->json('data.authorization_url');

        // Guard against an open-redirect if the Paystack response is ever tampered with.
        // Match the apex domain or a real subdomain — NOT any host merely ending in the
        // string (e.g. "evilpaystack.com" must not pass).
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $isPaystackHost = $host === 'paystack.com' || str_ends_with($host, '.paystack.com');
        if (($parsed['scheme'] ?? '') !== 'https' || ! $isPaystackHost) {
            Log::error('Paystack returned unexpected authorization URL', ['url' => $url]);
            throw new \RuntimeException('Invalid payment gateway response.');
        }

        return $url;
    }

    /**
     * Verify a transaction reference with Paystack and return the raw transaction data.
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->timeout(self::TIMEOUT_SECONDS)
            ->get(self::VERIFY_URL.$reference);

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack verification failed', [
                'reference' => $reference,
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('Payment verification failed.');
        }

        $data = $response->json('data');

        if ($data['status'] !== 'success') {
            throw new \RuntimeException('Payment was not successful.');
        }

        if (($data['currency'] ?? '') !== 'NGN') {
            Log::warning('Paystack transaction settled in unsupported currency', [
                'reference' => $reference,
                'currency' => $data['currency'] ?? null,
            ]);
            throw new \RuntimeException('Payment was made in an unsupported currency.');
        }

        return $data;
    }
}
