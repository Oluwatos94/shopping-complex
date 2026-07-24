<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Models\ChatMessage;

class MessageSentEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue on which to place the broadcasting job.
     */
    public string $queue = 'broadcasts';

    public function __construct(
        public readonly ChatMessage $message,
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
            new PrivateChannel("conversation.{$this->message->conversation_id}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
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
            'conversation_id' => $this->message->conversation_id,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => e($this->message->sender->name), // XSS protection
            ],
            'content' => e(Str::limit($this->message->content, 5000)), // XSS protection + limit
            'attachment' => $this->message->hasAttachment() ? [
                'url' => $this->message->getAttachmentUrl(),
                'type' => $this->message->attachment_type,
                'name' => e($this->message->attachment_name), // XSS protection
            ] : null,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
