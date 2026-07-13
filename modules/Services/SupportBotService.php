<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Log;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\SupportMessage;
use ModulesShoppingComplex\Repositories\SupportConversationRepository;
use ModulesShoppingComplex\Repositories\SupportMessageRepository;
use ModulesShoppingComplex\Services\Contracts\AiChatClient;

final readonly class SupportBotService
{
    private const MAX_HISTORY_MESSAGES = 20;

    private const FALLBACK_REPLY = "Sorry, I'm having trouble replying right now. Please try again in a moment, or ask to speak with a human agent and I'll connect you.";

    public function __construct(
        private AiChatClient $ai,
        private SupportConversationRepository $conversationRepository,
        private SupportMessageRepository $messageRepository,
    ) {}

    /**
     * Persist the user's message, generate an AI reply from the thread
     * history, and persist + return the assistant message.
     */
    public function reply(SupportConversation $conversation, string $userText): SupportMessage
    {
        $this->messageRepository->create([
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessageRoleEnum::USER,
            'sender_id' => $conversation->user_id,
            'content' => $userText,
        ]);

        $assistantMessage = $this->messageRepository->create([
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessageRoleEnum::ASSISTANT,
            'sender_id' => null,
            'content' => $this->generateReply($conversation),
        ]);

        $this->conversationRepository->updateLastMessageAt($conversation->id);

        return $assistantMessage;
    }

    private function generateReply(SupportConversation $conversation): string
    {
        try {
            $response = $this->ai->createMessage([
                'max_tokens' => 1024,
                'system' => $this->systemPrompt(),
                'messages' => $this->buildHistory($conversation),
            ]);

            foreach ((array) ($response['content'] ?? []) as $block) {
                if (($block['type'] ?? '') === 'text' && trim((string) $block['text']) !== '') {
                    return (string) $block['text'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Support bot reply failed', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }

        return self::FALLBACK_REPLY;
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildHistory(SupportConversation $conversation): array
    {
        return $this->messageRepository
            ->getRecentForConversation($conversation->id, self::MAX_HISTORY_MESSAGES)
            ->map(fn (SupportMessage $message) => [
                'role' => $message->role === SupportMessageRoleEnum::USER ? 'user' : 'assistant',
                'content' => $message->content,
            ])
            ->all();
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are the customer support assistant for Jiidaa, a Nigerian GPS-powered, WhatsApp-native marketplace that connects buyers to trusted local vendors near them.

WHAT YOU HELP WITH:
- Finding vendors or products: buyers browse the website or message the Jiidaa WhatsApp bot, share their location, and get the nearest matching vendors. Buying, negotiation and delivery happen directly between buyer and vendor on WhatsApp — Jiidaa does not process orders, buyer payments, or delivery.
- Becoming a vendor: register on the website, complete onboarding, wait for admin approval, add products and a business location, then choose a subscription plan to become discoverable.
- Vendor subscriptions: an active paid subscription keeps a vendor's products discoverable; plans and payment are managed from the vendor dashboard.
- Account issues and general questions about how Jiidaa works.

TONE & LANGUAGE:
- Be friendly, clear and concise.
- Reply in the SAME language the user writes in (English, Nigerian Pidgin, Yoruba, Hausa or Igbo) and keep it consistent. When unsure, use simple English.
- All prices are in Nigerian Naira (₦).

ACCURACY:
- Never invent vendors, products, prices, order details or account information. If you don't have access to something specific to the user's account, say so plainly.

HUMAN HANDOFF:
- If you cannot resolve the issue, if the user is frustrated, or if they ask for a person, offer to connect them to a human support agent and ask them to confirm they want that.
PROMPT;
    }
}
