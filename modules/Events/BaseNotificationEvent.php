<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Models\User;

abstract class BaseNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $recipient,
        public readonly string $message,
        public readonly array $data = []
    ) {}

    /**
     * Get the notification type identifier
     */
    abstract public function getNotificationType(): string;

    /**
     * Get the group key for notification grouping (optional)
     */
    public function getGroupKey(): ?string
    {
        return null;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->recipient->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'notification.'.$this->getNotificationType();
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->getNotificationType(),
            'message' => $this->message,
            'data' => $this->data,
            'created_at' => now()->toISOString(),
        ];
    }
}
