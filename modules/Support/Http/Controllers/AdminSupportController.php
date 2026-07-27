<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Support\Models\SupportConversation;
use ModulesShoppingComplex\Support\Repositories\SupportConversationRepository;

class AdminSupportController extends Controller
{
    private const PREVIEW_LENGTH = 100;

    public function __construct(
        private readonly SupportConversationRepository $conversationRepository,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Support', [
            'conversations' => $this->inboxPayload(),
        ]);
    }

    public function conversations(): JsonResponse
    {
        return response()->json([
            'conversations' => $this->inboxPayload(),
        ]);
    }

    public function markRead(SupportConversation $conversation): JsonResponse
    {
        $this->authorize('actAsAgent', $conversation);

        $conversation->agent_last_read_at = now();
        $this->conversationRepository->save($conversation);

        return response()->json(['success' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function inboxPayload(): array
    {
        return $this->conversationRepository
            ->getForInbox()
            ->through(fn (SupportConversation $conversation) => $this->toInboxItem($conversation))
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function toInboxItem(SupportConversation $conversation): array
    {
        $lastCustomer = $conversation->lastCustomerMessage;
        $lastMessage = $conversation->lastMessage;

        $unread = $lastCustomer !== null
            && ($conversation->agent_last_read_at === null
                || $lastCustomer->created_at->greaterThan($conversation->agent_last_read_at));

        return [
            'id' => $conversation->id,
            'user' => $conversation->user !== null
                ? ['id' => $conversation->user->id, 'name' => $conversation->user->name]
                : null,
            'agent' => $conversation->agent !== null
                ? ['id' => $conversation->agent->id, 'name' => $conversation->agent->name]
                : null,
            'status' => $conversation->status->value,
            'last_message_at' => $conversation->last_message_at?->toISOString(),
            'escalated_at' => $conversation->escalated_at?->toISOString(),
            'unread' => $unread,
            'last_message_preview' => $lastMessage !== null
                ? Str::limit($lastMessage->content, self::PREVIEW_LENGTH)
                : null,
        ];
    }
}
