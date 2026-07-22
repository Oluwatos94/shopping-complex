<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ModulesShoppingComplex\Http\Requests\SendSupportMessageRequest;
use ModulesShoppingComplex\Http\Requests\SupportConversationRequest;
use ModulesShoppingComplex\Models\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Repositories\SupportConversationRepository;
use ModulesShoppingComplex\Repositories\SupportMessageRepository;
use ModulesShoppingComplex\Services\SupportBotService;
use ModulesShoppingComplex\Services\SupportEscalationService;
use ModulesShoppingComplex\Shared\Http\Concerns\PaginatesResults;

class SupportController extends Controller
{
    use PaginatesResults;

    private const GUEST_MESSAGE_LIMIT = 30;

    public function __construct(
        private readonly SupportBotService $supportBotService,
        private readonly SupportEscalationService $escalationService,
        private readonly SupportConversationRepository $conversationRepository,
        private readonly SupportMessageRepository $messageRepository,
    ) {}

    public function store(SupportConversationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $conversation = $this->conversationRepository->findOpenForUser($user->id)
                ?? $this->conversationRepository->create([
                    'user_id' => $user->id,
                    'status' => SupportConversationStatusEnum::BOT,
                ]);
        } else {
            $conversation = $this->findGuestConversation($request);

            if ($conversation === null) {
                $conversation = $this->conversationRepository->create([
                    'user_id' => null,
                    'status' => SupportConversationStatusEnum::BOT,
                ]);
                $request->session()->put(SupportConversation::GUEST_SESSION_KEY, $conversation->id);
            }
        }

        return response()->json([
            'conversation' => $conversation,
            'message' => 'Conversation retrieved successfully',
        ], 200);
    }

    /**
     * GET /api/support/conversations/{conversation}
     * Get a support conversation and its status.
     */
    public function show(SupportConversation $conversation, Request $request): JsonResponse
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

    public function sendMessage(SendSupportMessageRequest $request, SupportConversation $conversation): JsonResponse
    {
        $user = $request->user();
        $content = (string) $request->validated('content');

        if ($user !== null && $user->role === 'admin' && $conversation->user_id !== $user->id) {
            $this->authorize('actAsAgent', $conversation);
            $message = $this->escalationService->agentReply($conversation, $user, $content);
        } elseif ($conversation->status === SupportConversationStatusEnum::WITH_AGENT) {
            $this->authorize('view', $conversation);
            $message = $this->escalationService->customerMessage($conversation, $content);
        } else {
            $this->authorize('view', $conversation);

            if ($conversation->user_id === null
                && $this->messageRepository->countByRole($conversation->id, SupportMessageRoleEnum::USER) >= self::GUEST_MESSAGE_LIMIT) {
                return response()->json([
                    'message' => 'This guest chat has reached its limit. Please sign in to keep getting help.',
                ], 429);
            }

            $lat = $request->validated('lat');
            $lng = $request->validated('lng');

            $message = $this->supportBotService->reply(
                $conversation,
                $content,
                $lat !== null ? (float) $lat : null,
                $lng !== null ? (float) $lng : null,
            );
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

    private function findGuestConversation(Request $request): ?SupportConversation
    {
        $id = (int) $request->session()->get(SupportConversation::GUEST_SESSION_KEY, 0);
        if ($id === 0) {
            return null;
        }

        $conversation = $this->conversationRepository->find($id);

        if ($conversation === null
            || $conversation->user_id !== null
            || $conversation->status === SupportConversationStatusEnum::RESOLVED) {
            return null;
        }

        return $conversation;
    }
}
