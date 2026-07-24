<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;

class ConversationRepository extends BasePageRepository
{
    /**
     * Get conversations for a user with pagination.
     *
     * @param  array<int|string, string|\Closure>  $relations
     */
    public function getForUser(int $userId, int $perPage = 20, array $relations = []): LengthAwarePaginator
    {
        $query = Conversation::query()
            ->where(function ($q) use ($userId) {
                $q->where('customer_id', $userId)
                    ->orWhere('vendor_id', $userId);
            })
            // Add unread count as a subquery to prevent N+1
            ->withCount(['messages as unread_count' => function ($q) use ($userId) {
                $q->where('sender_id', '!=', $userId)
                    ->whereNull('read_at');
            }])
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Find a conversation between two users, optionally for a specific product.
     */
    public function findBetweenUsers(int $customerId, int $vendorId, ?int $productId = null): ?Conversation
    {
        return Conversation::query()
            ->where('customer_id', $customerId)
            ->where('vendor_id', $vendorId)
            ->where('product_id', $productId)
            ->first();
    }

    /**
     * Find or create a conversation between two users.
     * Uses firstOrCreate to prevent race conditions with concurrent requests.
     */
    public function findOrCreate(int $customerId, int $vendorId, ?int $productId = null): Conversation
    {
        return DB::transaction(function () use ($customerId, $vendorId, $productId) {
            $conversation = Conversation::firstOrCreate(
                [
                    'customer_id' => $customerId,
                    'vendor_id' => $vendorId,
                    'product_id' => $productId,
                ]
            );

            // Invalidate channel authorization cache for both participants
            Cache::forget("ws.auth.conversation.{$conversation->id}.user.{$customerId}");
            Cache::forget("ws.auth.conversation.{$conversation->id}.user.{$vendorId}");

            return $conversation;
        });
    }

    /**
     * Find a conversation by ID.
     *
     * @param  array<string>  $relations
     */
    public function find(int $id, array $relations = []): ?Conversation
    {
        $query = Conversation::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Update the last message timestamp.
     */
    public function updateLastMessageAt(int $conversationId): void
    {
        Conversation::query()
            ->where('id', $conversationId)
            ->update(['last_message_at' => now()]);
    }

    /**
     * Get total unread count for a user across all conversations.
     */
    public function getTotalUnreadCount(int $userId): int
    {
        return DB::table('chat_messages')
            ->join('conversations', 'chat_messages.conversation_id', '=', 'conversations.id')
            ->where(function ($q) use ($userId) {
                $q->where('conversations.customer_id', $userId)
                    ->orWhere('conversations.vendor_id', $userId);
            })
            ->where('chat_messages.sender_id', '!=', $userId)
            ->whereNull('chat_messages.read_at')
            ->count();
    }

    /**
     * Get conversations with unread messages for a user.
     *
     * @return Collection<int, Conversation>
     */
    public function getWithUnreadMessages(int $userId): Collection
    {
        return Conversation::query()
            ->where(function ($q) use ($userId) {
                $q->where('customer_id', $userId)
                    ->orWhere('vendor_id', $userId);
            })
            ->whereHas('messages', function ($q) use ($userId) {
                $q->where('sender_id', '!=', $userId)
                    ->whereNull('read_at');
            })
            ->with(['customer', 'vendor', 'product'])
            ->orderBy('last_message_at', 'desc')
            ->get();
    }
}
