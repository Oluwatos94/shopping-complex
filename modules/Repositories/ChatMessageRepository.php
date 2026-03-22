<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\ChatMessage;

class ChatMessageRepository extends BasePageRepository
{
    /**
     * Get messages for a conversation with pagination.
     *
     * @param  array<string>  $relations
     */
    public function getForConversation(
        int $conversationId,
        int $perPage = 50,
        array $relations = []
    ): LengthAwarePaginator {
        $query = ChatMessage::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new message.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ChatMessage
    {
        return DB::transaction(function () use ($data) {
            return ChatMessage::create($data);
        });
    }

    /**
     * Find a message by ID.
     *
     * @param  array<string>  $relations
     */
    public function find(int $id, array $relations = []): ?ChatMessage
    {
        $query = ChatMessage::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Mark messages as read for a user in a conversation.
     *
     * @return int Number of messages marked as read
     */
    public function markAsRead(int $conversationId, int $userId): int
    {
        return ChatMessage::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Mark a message as delivered.
     */
    public function markAsDelivered(int $messageId): bool
    {
        return ChatMessage::query()
            ->where('id', $messageId)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => now()]) > 0;
    }

    /**
     * Get unread messages for a user in a conversation.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ChatMessage>
     */
    public function getUnreadForUser(int $conversationId, int $userId): Collection
    {
        return ChatMessage::query()
            ->where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get the last message in a conversation.
     */
    public function getLastMessage(int $conversationId): ?ChatMessage
    {
        return ChatMessage::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Delete a message.
     */
    public function delete(int $messageId): bool
    {
        return ChatMessage::query()
            ->where('id', $messageId)
            ->delete() > 0;
    }

    /**
     * Get messages after a specific message ID (for real-time updates).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ChatMessage>
     */
    public function getMessagesAfter(int $conversationId, int $afterMessageId): Collection
    {
        return ChatMessage::query()
            ->where('conversation_id', $conversationId)
            ->where('id', '>', $afterMessageId)
            ->orderBy('created_at', 'asc')
            ->with('sender')
            ->get();
    }
}
