<?php

declare(strict_types=1);

namespace Tests\Support;

use ModulesShoppingComplex\WhatsApp\Contracts\WhatsAppSender;

/**
 * Swallows WhatsApp sends so tests that charge leads don't reach the network
 * through the vendor lead alert.
 */
final class NullWhatsAppSender implements WhatsAppSender
{
    public function sendText(string $to, string $body): void {}

    public function sendTemplate(string $to, string $templateName, string $lang, array $components = []): void {}
}
