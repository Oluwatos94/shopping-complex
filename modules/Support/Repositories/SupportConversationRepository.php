<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Support\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;
use ModulesShoppingComplex\Support\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Support\Models\SupportConversation;

class SupportConversationRepository extends BasePageRepository
{
    /**
     * Get support threads for a user with pagination.
     *
     * @param  array<int|string, string|\Closure>  $relations
     */
    public function getForUser(int $userId, int $perPage = 20, array $relations = []): LengthAwarePaginator
    {
        $query = SupportConversation::query()
            ->where('user_id', $userId)
            ->orderBy('last_message_at', 'desc')
            ->orderBy('created_at', 'desc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get support threads awaiting a human agent (escalation queue), oldest first.
     *
     * @param  array<int|string, string|\Closure>  $relations
     */
    public function getAwaitingAgent(int $perPage = 20, array $relations = []): LengthAwarePaginator
    {
        $query = SupportConversation::query()
            ->where('status', SupportConversationStatusEnum::AWAITING_AGENT)
            ->orderBy('escalated_at', 'asc');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    public function getForInbox(int $perPage = 30): LengthAwarePaginator
    {
        return SupportConversation::query()
            ->where(function ($query) {
                $query->whereIn('status', [
                    SupportConversationStatusEnum::AWAITING_AGENT,
                    SupportConversationStatusEnum::WITH_AGENT,
                ])->orWhere(function ($resolved) {
                    $resolved->where('status', SupportConversationStatusEnum::RESOLVED)
                        ->where('last_message_at', '>=', now()->subDays(7));
                });
            })
            ->with(['user:id,name', 'agent:id,name', 'lastMessage', 'lastCustomerMessage'])
            ->orderByRaw('last_message_at IS NULL, last_message_at DESC')
            ->orderByDesc('escalated_at')
            ->paginate($perPage);
    }

    /**
     * Count threads currently waiting to be picked up by an agent.
     */
    public function countAwaitingAgent(): int
    {
        return SupportConversation::query()
            ->where('status', SupportConversationStatusEnum::AWAITING_AGENT)
            ->count();
    }

    /**
     * Get the user's most recent open (non-resolved) support thread.
     */
    public function findOpenForUser(int $userId): ?SupportConversation
    {
        return SupportConversation::query()
            ->where('user_id', $userId)
            ->where('status', '!=', SupportConversationStatusEnum::RESOLVED)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Find a support thread by ID.
     *
     * @param  array<string>  $relations
     */
    public function find(int $id, array $relations = []): ?SupportConversation
    {
        $query = SupportConversation::query();

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->find($id);
    }

    /**
     * Create a new support thread.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SupportConversation
    {
        return SupportConversation::create($data);
    }

    public function save(SupportConversation $conversation): void
    {
        $conversation->save();
    }

    public function updateLastMessageAt(int $conversationId): void
    {
        SupportConversation::query()
            ->where('id', $conversationId)
            ->update(['last_message_at' => now()]);
    }
}
