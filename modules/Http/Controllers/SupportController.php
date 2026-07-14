<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Concerns\PaginatesResults;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ModulesShoppingComplex\Http\Requests\SendSupportMessageRequest;
use ModulesShoppingComplex\Http\Requests\SupportConversationRequest;
use ModulesShoppingComplex\Models\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Repositories\SupportConversationRepository;
use ModulesShoppingComplex\Repositories\SupportMessageRepository;
use ModulesShoppingComplex\Services\SupportBotService;
use ModulesShoppingComplex\Services\SupportEscalationService;

class SupportController extends Controller
{
    use PaginatesResults;

    public function __construct(
        private readonly SupportBotService $supportBotService,
        private readonly SupportEscalationService $escalationService,
        private readonly SupportConversationRepository $conversationRepository,
        private readonly SupportMessageRepository $messageRepository,
    ) {}

    /**
     * POST /api/support/conversations
     * Start a support conversation, reusing the user's open one if any.
     */
    public function store(SupportConversationRequest $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversation = $this->conversationRepository->findOpenForUser($userId)
            ?? $this->conversationRepository->create([
                'user_id' => $userId,
                'status' => SupportConversationStatusEnum::BOT,
            ]);

        return response()->json([
            'conversation' => $conversation,
            'message' => 'Conversation retrieved successfully',
        ], 200);
    }

    /**
     * GET /api/support/conversations/{conversation}
     * Get a support conversation and its status.
     */
    public function show(SupportConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    /**
     * GET /api/support/conversations/{conversation}/messages
     * Get messages for a support conversation with pagination.
     */
    public function messages(SupportConversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $perPage = min((int) $request->get('per_page', 50), 100);
        $messages = $this->messageRepository->getForConversation($conversation->id, $perPage);

        return $this->paginatedResponse($messages, 'messages');
    }

    /**
     * POST /api/support/conversations/{conversation}/messages
     * Customers get a bot reply back (unless a human handoff is in progress);
     * admins reply as the human agent, claiming the conversation.
     */
    public function sendMessage(SendSupportMessageRequest $request, SupportConversation $conversation): JsonResponse
    {
        $user = $request->user();
        $content = (string) $request->validated('content');

        if ($user->role === 'admin' && $conversation->user_id !== $user->id) {
            $this->authorize('actAsAgent', $conversation);
            $message = $this->escalationService->agentReply($conversation, $user, $content);
        } elseif (in_array($conversation->status, [
            SupportConversationStatusEnum::AWAITING_AGENT,
            SupportConversationStatusEnum::WITH_AGENT,
        ], true)) {
            $this->authorize('view', $conversation);
            $message = $this->escalationService->customerMessage($conversation, $content);
        } else {
            $this->authorize('view', $conversation);
            $message = $this->supportBotService->reply($conversation, $content);
        }

        return response()->json([
            'message' => $message,
            'success' => true,
        ], 201);
    }

    /**
     * POST /api/support/conversations/{conversation}/escalate
     * Customer-initiated "talk to a human" handoff.
     */
    public function escalate(SupportConversation $conversation): JsonResponse
    {
        $this->authorize('escalate', $conversation);

        $conversation = $this->escalationService->escalate($conversation);

        return response()->json([
            'conversation' => $conversation,
            'message' => 'A human agent has been notified',
        ]);
    }

    /**
     * POST /api/support/conversations/{conversation}/resolve
     * Mark the conversation as resolved.
     */
    public function resolve(SupportConversation $conversation): JsonResponse
    {
        $this->authorize('resolve', $conversation);

        $conversation = $this->escalationService->resolve($conversation);

        return response()->json([
            'conversation' => $conversation,
            'message' => 'Conversation resolved',
        ]);
    }
}
