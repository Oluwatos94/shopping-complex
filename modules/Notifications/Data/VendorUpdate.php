<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Data;

use ModulesShoppingComplex\Notifications\Enums\NotificationChannelEnum;

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
        public ?string $bannerUrl = null,
        public array $channels = [],
    ) {}

    /**
     * An empty channel list means every channel, so existing senders that do
     * not choose keep fanning out to all three.
     */
    public function sendsTo(NotificationChannelEnum $channel): bool
    {
        return $this->channels === [] || in_array($channel->value, $this->channels, true);
    }
}
