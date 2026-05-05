<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final readonly class GeminiClient
{
    private string $baseUrl;

    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-2.0-flash',
    ) {
        $this->baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function generateContent(array $payload): array
    {
        $response = Http::timeout(30)
            ->post("{$this->baseUrl}?key={$this->apiKey}", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gemini API error: '.$response->body());
        }

        return (array) $response->json();
    }
}
