<?php

declare(strict_types=1);

namespace Tests\Support;

use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;

/**
 * Captures WhatsApp sends so tests can assert who was told what.
 */
final class RecordingWhatsAppSender implements WhatsAppSender
{
    /** @var array<int, array{to: string, body: string}> */
    public array $texts = [];

    /** @var array<int, array{to: string, template: string, components: array<int, array<string, mixed>>}> */
    public array $templates = [];

    public function sendText(string $to, string $body): void
    {
        $this->texts[] = ['to' => $to, 'body' => $body];
    }

    public function sendTemplate(string $to, string $templateName, string $lang, array $components = []): void
    {
        $this->templates[] = ['to' => $to, 'template' => $templateName, 'components' => $components];
    }

    /**
     * @return list<string>
     */
    public function textsTo(string $to): array
    {
        return array_values(array_map(
            fn (array $text): string => $text['body'],
            array_filter($this->texts, fn (array $text): bool => $text['to'] === $to),
        ));
    }
}
