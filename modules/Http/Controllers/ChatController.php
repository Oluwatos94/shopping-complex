<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\SendMessageRequest;
use ModulesShoppingComplex\Http\Requests\StartConversationRequest;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Services\ChatService;
use ModulesShoppingComplex\Shared\Http\Concerns\PaginatesResults;

class ChatController extends Controller
{
    use PaginatesResults;

    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 50);
        $conversations = $this->chatService->getConversations($request->user(), $perPage);

        return $this->paginatedResponse($conversations, 'conversations');
    }

    public function store(StartConversationRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Determine customer and vendor based on user role
        if ($user->role === 'customer') {
            $customer = $user;
            $vendor = User::findOrFail($validated['vendor_id']);
        } else {
            $customer = User::findOrFail($validated['customer_id']);
            $vendor = $user;
        }

        $conversation = $this->chatService->getOrCreateConversation(
            $customer,
            $vendor,
            $validated['product_id'] ?? null
        );

        $conversation->load(['customer', 'vendor', 'product']);

        return response()->json([
            'conversation' => $conversation,
            'message' => 'Conversation retrieved successfully',
        ], 200);
    }

    public function show(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['customer', 'vendor', 'product']);

        return response()->json([
            'conversation' => $conversation,
            'unread_count' => $conversation->getUnreadCountFor($request->user()->id),
        ]);
    }

    public function messages(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $perPage = min((int) $request->get('per_page', 50), 100);
        $messages = $this->chatService->getMessages($conversation, $perPage);

        return $this->paginatedResponse($messages, 'messages');
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $message = $this->chatService->sendMessage(
            $conversation,
            $request->user(),
            $request->validated('content'),
            $request->file('attachment')
        );

        return response()->json([
            'message' => $message,
            'success' => true,
        ], 201);
    }

    public function markAsRead(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $count = $this->chatService->markMessagesAsRead($conversation, $request->user());

        return response()->json([
            'message' => 'Messages marked as read',
            'count' => $count,
        ]);
    }

    public function typing(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $this->chatService->sendTypingIndicator($conversation, $request->user());

        return response()->json([
            'message' => 'Typing indicator sent',
        ]);
    }

    public function pollMessages(Conversation $conversation, Request $request): JsonResponse
    {
        $this->authorize('view', $conversation);

        $afterMessageId = (int) $request->get('after_message_id', 0);
        $messages = $this->chatService->getMessagesAfter($conversation, $afterMessageId);

        return response()->json([
            'messages' => $messages,
            'has_new' => $messages->isNotEmpty(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->chatService->getTotalUnreadCount($request->user());

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    public function chatPage(Request $request): Response
    {
        $conversations = $this->chatService->getConversations($request->user(), 20);

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations->items(),
            'pagination' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    public function conversationPage(Conversation $conversation, Request $request): Response
    {
        $this->authorize('view', $conversation);

        $conversation->load(['customer', 'vendor', 'product']);
        $messages = $this->chatService->getMessages($conversation, 50);

        $this->chatService->markMessagesAsRead($conversation, $request->user());

        $conversations = $this->chatService->getConversations($request->user(), 20);

        return Inertia::render('Chat/Conversation', [
            'conversation' => $conversation,
            'messages' => $messages->items(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'total' => $messages->total(),
            ],
            'conversations' => $conversations->items(),
        ]);
    }
}
