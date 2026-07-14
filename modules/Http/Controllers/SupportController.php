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

class SupportController extends Controller
{
    use PaginatesResults;

    public function __construct(
        private readonly SupportBotService $supportBotService,
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
     * Send a message and get the bot's reply back.
     */
    public function sendMessage(SendSupportMessageRequest $request, SupportConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $message = $this->supportBotService->reply(
            $conversation,
            (string) $request->validated('content'),
        );

        return response()->json([
            'message' => $message,
            'success' => true,
        ], 201);
    }
}
