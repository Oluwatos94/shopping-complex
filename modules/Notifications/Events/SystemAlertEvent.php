<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Events;

use ModulesShoppingComplex\Models\User;

class SystemAlertEvent extends BaseNotificationEvent
{
    /**
     * @param  string  $alertLevel  One of: 'info', 'warning', 'critical'
     */
    public function __construct(
        User $recipient,
        string $message,
        public readonly string $alertLevel = 'info',
        array $data = []
    ) {
        parent::__construct(
            recipient: $recipient,
            message: $message,
            data: array_merge($data, ['alert_level' => $alertLevel])
        );
    }

    public function getNotificationType(): string
    {
        return 'system_alert';
    }

    public function getGroupKey(): ?string
    {
        return 'system_alerts';
    }
}
