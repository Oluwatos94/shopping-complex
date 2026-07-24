<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\WhatsApp\Contracts;

interface WhatsAppSender
{
    public function sendText(string $to, string $body): void;
}
