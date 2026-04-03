<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

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

        // Guard against an open-redirect if the Paystack response is ever tampered with
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || ! str_ends_with($host, 'paystack.com')) {
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

        return $data;
    }
}
