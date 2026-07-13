<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\SupportMessage;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Services\Contracts\AiChatClient;
use ModulesShoppingComplex\Services\SupportBotService;
use RuntimeException;
use Tests\TestCase;

class FakeAiChatClient implements AiChatClient
{
    /** @var array<int, array<string, mixed>> */
    public array $payloads = [];

    public function __construct(
        private string $replyText = 'Hello! How can I help you today?',
        private bool $shouldThrow = false,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createMessage(array $payload): array
    {
        $this->payloads[] = $payload;

        if ($this->shouldThrow) {
            throw new RuntimeException('Gemini API error (HTTP 500).');
        }

        return [
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => $this->replyText]],
        ];
    }
}

class SupportBotServiceTest extends TestCase
{
    use RefreshDatabase;

    private function bindFakeAi(string $replyText = 'Hello! How can I help you today?', bool $shouldThrow = false): FakeAiChatClient
    {
        $fake = new FakeAiChatClient($replyText, $shouldThrow);
        $this->app->instance(AiChatClient::class, $fake);

        return $fake;
    }

    public function test_reply_persists_user_then_assistant_message(): void
    {
        $this->bindFakeAi('You can find vendors on the products page.');
        $conversation = SupportConversation::factory()->create();

        $service = $this->app->make(SupportBotService::class);
        $reply = $service->reply($conversation, 'How do I find a vendor?');

        $this->assertSame(SupportMessageRoleEnum::ASSISTANT, $reply->role);
        $this->assertSame('You can find vendors on the products page.', $reply->content);
        $this->assertNull($reply->sender_id);

        $messages = SupportMessage::query()
            ->where('support_conversation_id', $conversation->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $messages);
        $this->assertSame(SupportMessageRoleEnum::USER, $messages[0]->role);
        $this->assertSame('How do I find a vendor?', $messages[0]->content);
        $this->assertSame($conversation->user_id, $messages[0]->sender_id);
        $this->assertSame(SupportMessageRoleEnum::ASSISTANT, $messages[1]->role);

        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_history_is_ordered_and_role_mapped(): void
    {
        $fake = $this->bindFakeAi();
        $conversation = SupportConversation::factory()->create();
        $agent = User::factory()->create();

        SupportMessage::factory()->forConversation($conversation)->create(['content' => 'My order is late']);
        SupportMessage::factory()->forConversation($conversation)->fromAssistant()->create(['content' => 'Let me check that for you']);
        SupportMessage::factory()->forConversation($conversation)->fromAgent($agent)->create(['content' => 'Agent here, following up']);

        $this->app->make(SupportBotService::class)->reply($conversation, 'Any update?');

        $this->assertCount(1, $fake->payloads);
        $this->assertNotEmpty($fake->payloads[0]['system']);

        $history = $fake->payloads[0]['messages'];
        $this->assertSame(
            [
                ['role' => 'user', 'content' => 'My order is late'],
                ['role' => 'assistant', 'content' => 'Let me check that for you'],
                ['role' => 'assistant', 'content' => 'Agent here, following up'],
                ['role' => 'user', 'content' => 'Any update?'],
            ],
            $history,
        );
    }

    public function test_history_is_trimmed_to_a_window(): void
    {
        $fake = $this->bindFakeAi();
        $conversation = SupportConversation::factory()->create();

        SupportMessage::factory()->count(25)->forConversation($conversation)->create();

        $this->app->make(SupportBotService::class)->reply($conversation, 'Latest question');

        $history = $fake->payloads[0]['messages'];
        $this->assertCount(20, $history);
        $this->assertSame('Latest question', end($history)['content']);
    }

    public function test_ai_failure_returns_friendly_fallback(): void
    {
        $this->bindFakeAi(shouldThrow: true);
        $conversation = SupportConversation::factory()->create();

        $reply = $this->app->make(SupportBotService::class)->reply($conversation, 'Help me please');

        $this->assertSame(SupportMessageRoleEnum::ASSISTANT, $reply->role);
        $this->assertStringContainsString('human agent', $reply->content);

        $this->assertSame(
            2,
            SupportMessage::query()->where('support_conversation_id', $conversation->id)->count(),
        );
    }
}
