<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Data;

final readonly class VendorUpdate
{
    public function __construct(
        public string $subject,
        public string $body,
        public ?string $ctaLabel = null,
        public ?string $ctaUrl = null,
        public ?string $templateName = null,
        public ?string $templateLanguage = null,
        public array $templateComponents = [],
        public array $data = [],
    ) {}
}
