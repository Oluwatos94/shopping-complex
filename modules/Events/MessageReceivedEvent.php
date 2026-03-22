<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use ModulesShoppingComplex\Models\User;

class MessageReceivedEvent extends BaseNotificationEvent
{
    public function __construct(User $recipient, public readonly User $sender, string $messagePreview)
    {
        parent::__construct(
            recipient: $recipient,
            message: "{$sender->name} sent you a message",
            data: [
                'sender_id' => $sender->id,
                'sender_name' => $sender->name,
                'message_preview' => $messagePreview,
            ]
        );
    }

    public function getNotificationType(): string
    {
        return 'message_received';
    }

    public function getGroupKey(): ?string
    {
        return "message_from_{$this->sender->id}";
    }
}
