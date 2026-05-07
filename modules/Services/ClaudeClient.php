<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final readonly class ClaudeClient
{
    private const BASE_URL = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    public function __construct(
        private string $apiKey,
        private string $model = 'claude-haiku-4-5-20251001',
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMessage(array $payload): array
    {
        $payload['model'] ??= $this->model;
        $lastResponse = null;

        for ($attempt = 0; $attempt <= 3; $attempt++) {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => self::API_VERSION,
                ])
                ->post(self::BASE_URL, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (! is_array($data)) {
                    Log::error('Claude API returned non-JSON response', ['body' => $response->body()]);
                    throw new RuntimeException('Claude API returned a non-JSON response.');
                }

                return $data;
            }

            $status = $response->status();
            $lastResponse = $response;

            if (! in_array($status, [429, 529]) || $attempt === 3) {
                break;
            }

            $delay = (int) ($response->header('retry-after') ?: (2 ** $attempt));
            sleep(min($delay, 16));
        }

        Log::error('Claude API error', [
            'status' => $lastResponse?->status(),
            'body' => $lastResponse?->body(),
        ]);
        throw new RuntimeException('Claude API error (HTTP '.($lastResponse?->status() ?? 'unknown').').');
    }
}
