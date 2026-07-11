<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Models\SupportMessage;

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
