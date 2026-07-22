<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Models\SupportMessage;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;

class SupportMessageRepository extends BasePageRepository
{
    /**
     * Get messages for a support thread with pagination.
     *
     * @param  array<string>  $relations
     */
    public function getForConversation(
        int $conversationId,
        int $perPage = 50,
        array $relations = []
    ): LengthAwarePaginator {
        $query = SupportMessage::query()
            ->where('support_conversation_id', $conversationId)
            ->orderBy('created_at', 'desc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new support message.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupportMessage
    {
        return DB::transaction(function () use ($data) {
            return SupportMessage::create($data);
        });
    }

    /**
     * Find a support message by ID.
     *
     * @param  array<string>  $relations
     */
    public function find(int $id, array $relations = []): ?SupportMessage
    {
        $query = SupportMessage::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Get the most recent messages in a support thread, oldest first.
     *
     * @return Collection<int, SupportMessage>
     */
    public function getRecentForConversation(int $conversationId, int $limit): Collection
    {
        return SupportMessage::query()
            ->where('support_conversation_id', $conversationId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Count messages with a given role in a support thread.
     */
    public function countByRole(int $conversationId, SupportMessageRoleEnum $role): int
    {
        return SupportMessage::query()
            ->where('support_conversation_id', $conversationId)
            ->where('role', $role)
            ->count();
    }

    /**
     * Get the last message in a support thread.
     */
    public function getLastMessage(int $conversationId): ?SupportMessage
    {
        return SupportMessage::query()
            ->where('support_conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get messages after a specific message ID (for real-time polling).
     *
     * @return Collection<int, SupportMessage>
     */
    public function getMessagesAfter(int $conversationId, int $afterMessageId): Collection
    {
        return SupportMessage::query()
            ->where('support_conversation_id', $conversationId)
            ->where('id', '>', $afterMessageId)
            ->orderBy('created_at', 'asc')
            ->with('sender')
            ->get();
    }
}
