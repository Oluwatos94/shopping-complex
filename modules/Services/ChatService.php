<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ModulesShoppingComplex\Events\MessageReadEvent;
use ModulesShoppingComplex\Events\MessageSentEvent;
use ModulesShoppingComplex\Events\TypingIndicatorEvent;
use ModulesShoppingComplex\Models\ChatMessage;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\ChatMessageRepository;
use ModulesShoppingComplex\Repositories\ConversationRepository;

final readonly class ChatService
{
    public function __construct(
        private ConversationRepository $conversationRepository,
        private ChatMessageRepository $messageRepository,
    ) {}

    /**
     * Get conversations for a user.
     */
    public function getConversations(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->conversationRepository->getForUser(
            $user->id,
            $perPage,
            ['customer', 'vendor', 'product', 'messages' => fn ($q) => $q->latest()->limit(1)]
        );
    }

    /**
     * Get or create a conversation between a customer and vendor.
     */
    public function getOrCreateConversation(
        User $customer,
        User $vendor,
        ?int $productId = null
    ): Conversation {
        return $this->conversationRepository->findOrCreate(
            $customer->id,
            $vendor->id,
            $productId
        );
    }

    /**
     * Get messages for a conversation.
     */
    public function getMessages(Conversation $conversation, int $perPage = 50): LengthAwarePaginator
    {
        return $this->messageRepository->getForConversation(
            $conversation->id,
            $perPage,
            ['sender']
        );
    }

    /**
     * Send a message in a conversation.
     * Uses database transaction to ensure atomicity.
     */
    public function sendMessage(
        Conversation $conversation,
        User $sender,
        string $content,
        ?UploadedFile $attachment = null
    ): ChatMessage {
        return DB::transaction(function () use ($conversation, $sender, $content, $attachment) {
            $attachmentData = [];

            if ($attachment) {
                $attachmentData = $this->processAttachment($attachment, $conversation->id);
            }

            $message = $this->messageRepository->create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => $content,
                ...$attachmentData,
            ]);

            $this->conversationRepository->updateLastMessageAt($conversation->id);

            // Load sender relationship for broadcasting
            $message->load('sender');

            // Broadcast the message to the recipient (outside transaction would be better for performance,
            // but keeping inside ensures message exists before broadcast)
            $recipient = $conversation->getOtherParticipant($sender->id);
            if ($recipient) {
                event(new MessageSentEvent($message, $recipient));
            }

            return $message;
        });
    }

    /**
     * Mark messages as read in a conversation.
     *
     * @return int Number of messages marked as read
     */
    public function markMessagesAsRead(Conversation $conversation, User $reader): int
    {
        $count = $this->messageRepository->markAsRead($conversation->id, $reader->id);

        if ($count > 0) {
            // Broadcast read receipt to the other participant
            $otherParticipant = $conversation->getOtherParticipant($reader->id);
            if ($otherParticipant) {
                event(new MessageReadEvent($conversation, $reader, $otherParticipant));
            }
        }

        return $count;
    }

    public function sendTypingIndicator(Conversation $conversation, User $typer): void
    {
        $recipient = $conversation->getOtherParticipant($typer->id);

        if ($recipient) {
            event(new TypingIndicatorEvent($conversation, $typer, $recipient));
        }
    }

    public function getTotalUnreadCount(User $user): int
    {
        return $this->conversationRepository->getTotalUnreadCount($user->id);
    }

    /**
     * Get a conversation by ID if the user is a participant.
     */
    public function getConversationForUser(int $conversationId, User $user): ?Conversation
    {
        $conversation = $this->conversationRepository->find($conversationId, ['customer', 'vendor', 'product']);

        if (! $conversation || ! $conversation->hasParticipant($user->id)) {
            return null;
        }

        return $conversation;
    }

    /**
     * Process and store an attachment.
     *
     * @return array<string, string|null>
     */
    private function processAttachment(UploadedFile $file, int $conversationId): array
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        // Determine attachment type
        $type = match (true) {
            str_starts_with($mimeType ?? '', 'image/') => 'image',
            str_starts_with($mimeType ?? '', 'video/') => 'video',
            str_starts_with($mimeType ?? '', 'audio/') => 'audio',
            default => 'document',
        };

        // Store the file
        $path = $file->store("chat/{$conversationId}", 'public');

        return [
            'attachment_path' => $path,
            'attachment_type' => $type,
            'attachment_name' => $originalName,
        ];
    }

    /**
     * Delete an attachment from storage.
     */
    public function deleteAttachment(ChatMessage $message): void
    {
        if ($message->hasAttachment() && $message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }
    }

    /**
     * Get messages after a specific message ID (for polling/real-time).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ChatMessage>
     */
    public function getMessagesAfter(Conversation $conversation, int $afterMessageId): Collection
    {
        return $this->messageRepository->getMessagesAfter($conversation->id, $afterMessageId);
    }
}
