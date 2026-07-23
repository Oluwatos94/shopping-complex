<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Support\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Support\Models\SupportMessage;

class SupportMessageSentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $queue = 'broadcasts';

    public function __construct(
        public readonly SupportMessage $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("support-conversation.{$this->message->support_conversation_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.message.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'support_conversation_id' => $this->message->support_conversation_id,
            'role' => $this->message->role->value,
            'sender' => $this->message->sender !== null ? [
                'id' => $this->message->sender->id,
                'name' => e($this->message->sender->name), // XSS protection
            ] : null,
            'content' => e(Str::limit($this->message->content, 5000)), // XSS protection + limit
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
