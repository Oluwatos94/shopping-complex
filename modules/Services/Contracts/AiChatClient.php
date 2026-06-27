<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Contracts;

interface AiChatClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMessage(array $payload): array;
}
