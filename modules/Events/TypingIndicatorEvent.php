<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Models\Conversation;

/**
 * Typing indicator event - uses ShouldBroadcastNow for immediate delivery
 * since typing indicators are time-sensitive and low-payload.
 */
class TypingIndicatorEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly User $typer,
        public readonly User $recipient
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->conversation->id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user' => [
                'id' => $this->typer->id,
                'name' => e($this->typer->name), // XSS protection
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
