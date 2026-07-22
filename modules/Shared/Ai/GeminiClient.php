<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Shared\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Shared\Contracts\AiChatClient;
use RuntimeException;

final readonly class GeminiClient implements AiChatClient
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        private string $apiKey,
        private string $model = 'gemini-2.5-flash',
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMessage(array $payload): array
    {
        $request = $this->toGeminiRequest($payload);
        $url = self::BASE_URL.'/'.$this->model.':generateContent';
        $lastResponse = null;

        for ($attempt = 0; $attempt <= 3; $attempt++) {
            $response = Http::timeout(30)
                ->withHeaders(['x-goog-api-key' => $this->apiKey])
                ->post($url, $request);

            if ($response->successful()) {
                $data = $response->json();
                if (! is_array($data)) {
                    Log::error('Gemini API returned non-JSON response', ['body' => $response->body()]);
                    throw new RuntimeException('Gemini API returned a non-JSON response.');
                }

                return $this->toAnthropicResponse($data);
            }

            $status = $response->status();
            $lastResponse = $response;

            if (! in_array($status, [429, 500, 503]) || $attempt === 3) {
                break;
            }

            $delay = (int) ($response->header('retry-after') ?: (2 ** $attempt));
            sleep(min($delay, 16));
        }

        Log::error('Gemini API error', [
            'status' => $lastResponse->status(),
            'body' => $lastResponse->body(),
        ]);
        throw new RuntimeException('Gemini API error (HTTP '.$lastResponse->status().').');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function toGeminiRequest(array $payload): array
    {
        $request = [
            'contents' => $this->toGeminiContents((array) ($payload['messages'] ?? [])),
        ];

        if (! empty($payload['system'])) {
            $request['systemInstruction'] = [
                'parts' => [['text' => (string) $payload['system']]],
            ];
        }

        $tools = $this->toGeminiTools((array) ($payload['tools'] ?? []));
        if ($tools !== []) {
            $request['tools'] = [['functionDeclarations' => $tools]];
        }

        if (isset($payload['max_tokens'])) {
            $request['generationConfig'] = ['maxOutputTokens' => (int) $payload['max_tokens']];
        }

        return $request;
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function toGeminiContents(array $messages): array
    {
        $contents = [];

        foreach ($messages as $message) {
            $role = ($message['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $content = $message['content'] ?? '';

            if (is_string($content)) {
                $contents[] = ['role' => $role, 'parts' => [['text' => $content]]];

                continue;
            }

            $parts = [];
            foreach ((array) $content as $block) {
                $parts[] = $this->blockToPart((array) $block);
            }

            $parts = array_values(array_filter($parts));
            if ($parts !== []) {
                $contents[] = ['role' => $role, 'parts' => $parts];
            }
        }

        return $contents;
    }

    /**
     * Convert a single Anthropic content block into a Gemini part.
     *
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>|null
     */
    private function blockToPart(array $block): ?array
    {
        return match ($block['type'] ?? '') {
            'text' => ['text' => (string) ($block['text'] ?? '')],
            'tool_use' => ['functionCall' => [
                'name' => (string) ($block['name'] ?? ''),
                'args' => (object) ((array) ($block['input'] ?? [])),
            ]],
            'tool_result' => ['functionResponse' => [
                // tool_use_id is encoded as "name::uniqid" so we can recover the
                // function name Gemini requires (Anthropic results omit it).
                'name' => $this->nameFromToolUseId((string) ($block['tool_use_id'] ?? '')),
                'response' => ['result' => (string) ($block['content'] ?? '')],
            ]],
            default => null,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function toGeminiTools(array $tools): array
    {
        return array_map(function (array $tool) {
            $declaration = [
                'name' => (string) ($tool['name'] ?? ''),
                'description' => (string) ($tool['description'] ?? ''),
            ];

            $schema = $tool['input_schema'] ?? null;
            if (is_array($schema) && ! empty($schema['properties'])) {
                $declaration['parameters'] = $schema;
            }

            return $declaration;
        }, $tools);
    }

    /**
     * Translate a Gemini response into the Anthropic response shape.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function toAnthropicResponse(array $data): array
    {
        $parts = (array) data_get($data, 'candidates.0.content.parts', []);

        $content = [];
        $hasToolUse = false;

        foreach ($parts as $part) {
            if (isset($part['functionCall'])) {
                $hasToolUse = true;
                $name = (string) ($part['functionCall']['name'] ?? '');
                $content[] = [
                    'type' => 'tool_use',
                    'id' => $name.'::'.uniqid(),
                    'name' => $name,
                    'input' => (array) ($part['functionCall']['args'] ?? []),
                ];

                continue;
            }

            if (isset($part['text'])) {
                $content[] = ['type' => 'text', 'text' => (string) $part['text']];
            }
        }

        return [
            'stop_reason' => $hasToolUse ? 'tool_use' : 'end_turn',
            'content' => $content,
        ];
    }

    private function nameFromToolUseId(string $toolUseId): string
    {
        $pos = strpos($toolUseId, '::');

        return $pos === false ? $toolUseId : substr($toolUseId, 0, $pos);
    }
}
