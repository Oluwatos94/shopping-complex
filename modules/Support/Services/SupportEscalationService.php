<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Support\Services;

use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\Events\SystemAlertEvent;
use ModulesShoppingComplex\Notifications\Services\NotificationService;
use ModulesShoppingComplex\Repositories\UserRepository;
use ModulesShoppingComplex\Support\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Support\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Support\Events\SupportMessageSentEvent;
use ModulesShoppingComplex\Support\Models\SupportConversation;
use ModulesShoppingComplex\Support\Models\SupportMessage;
use ModulesShoppingComplex\Support\Repositories\SupportConversationRepository;
use ModulesShoppingComplex\Support\Repositories\SupportMessageRepository;

final readonly class SupportEscalationService
{
    public function __construct(
        private SupportConversationRepository $conversationRepository,
        private SupportMessageRepository $messageRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
    ) {}

    /**
     * Hand the conversation off to a human agent and notify all admins.
     * Idempotent: does nothing if a handoff is already in progress.
     */
    public function escalate(SupportConversation $conversation): SupportConversation
    {
        if (in_array($conversation->status, [
            SupportConversationStatusEnum::AWAITING_AGENT,
            SupportConversationStatusEnum::WITH_AGENT,
        ], true)) {
            return $conversation;
        }

        $conversation->status = SupportConversationStatusEnum::AWAITING_AGENT;
        $conversation->escalated_at = now();
        $this->conversationRepository->save($conversation);

        foreach ($this->userRepository->findByRole('admin') as $admin) {
            $this->notificationService->send(new SystemAlertEvent(
                recipient: $admin,
                message: 'A support conversation is waiting for a human agent.',
                alertLevel: 'warning',
                data: [
                    'action' => 'support_escalation',
                    'support_conversation_id' => $conversation->id,
                ],
            ));
        }

        return $conversation;
    }

    public function agentReply(SupportConversation $conversation, User $agent, string $content): SupportMessage
    {
        $conversation->status = SupportConversationStatusEnum::WITH_AGENT;
        $conversation->agent_id ??= $agent->id;
        $this->conversationRepository->save($conversation);

        $message = $this->messageRepository->create([
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessageRoleEnum::AGENT,
            'sender_id' => $agent->id,
            'content' => $content,
        ]);

        $this->conversationRepository->updateLastMessageAt($conversation->id);

        event(new SupportMessageSentEvent($message));

        return $message;
    }

    public function customerMessage(SupportConversation $conversation, string $content): SupportMessage
    {
        $message = $this->messageRepository->create([
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessageRoleEnum::USER,
            'sender_id' => $conversation->user_id,
            'content' => $content,
        ]);

        $this->conversationRepository->updateLastMessageAt($conversation->id);

        event(new SupportMessageSentEvent($message));

        return $message;
    }

    public function resolve(SupportConversation $conversation): SupportConversation
    {
        $conversation->status = SupportConversationStatusEnum::RESOLVED;
        $this->conversationRepository->save($conversation);

        return $conversation;
    }
}
