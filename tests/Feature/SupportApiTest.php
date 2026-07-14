<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use ModulesShoppingComplex\Models\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\SupportMessage;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Services\Contracts\AiChatClient;
use Tests\TestCase;

class SupportApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function bindFakeAi(string $replyText = 'Hello! How can I help you today?'): FakeAiChatClient
    {
        $fake = new FakeAiChatClient($replyText);
        $this->app->instance(AiChatClient::class, $fake);

        return $fake;
    }

    public function test_start_creates_a_bot_conversation_for_the_user(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->postJson('/api/support/conversations');

        $response->assertOk()
            ->assertJsonPath('conversation.user_id', $user->id)
            ->assertJsonPath('conversation.status', SupportConversationStatusEnum::BOT->value);
    }

    public function test_start_reuses_the_users_open_conversation(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $existing = SupportConversation::factory()->forUser($user)->create();

        $response = $this->actingAs($user)->postJson('/api/support/conversations');

        $response->assertOk()->assertJsonPath('conversation.id', $existing->id);
        $this->assertSame(1, SupportConversation::query()->count());
    }

    public function test_start_creates_a_new_conversation_when_previous_is_resolved(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $resolved = SupportConversation::factory()->forUser($user)->create([
            'status' => SupportConversationStatusEnum::RESOLVED,
        ]);

        $response = $this->actingAs($user)->postJson('/api/support/conversations');

        $response->assertOk();
        $this->assertNotSame($resolved->id, $response->json('conversation.id'));
    }

    public function test_start_send_and_history_round_trip(): void
    {
        $this->bindFakeAi('You can browse vendors on the products page.');
        $user = User::factory()->create(['role' => 'customer']);

        $conversationId = $this->actingAs($user)
            ->postJson('/api/support/conversations')
            ->json('conversation.id');

        $send = $this->postJson("/api/support/conversations/{$conversationId}/messages", [
            'content' => 'How do I find a vendor?',
        ]);

        $send->assertCreated()
            ->assertJsonPath('message.content', 'You can browse vendors on the products page.')
            ->assertJsonPath('message.support_conversation_id', $conversationId);

        $history = $this->getJson("/api/support/conversations/{$conversationId}/messages");
        $history->assertOk()->assertJsonCount(2, 'messages');

        $contents = array_column($history->json('messages'), 'content');
        $this->assertContains('How do I find a vendor?', $contents);
        $this->assertContains('You can browse vendors on the products page.', $contents);

        $this->getJson("/api/support/conversations/{$conversationId}")
            ->assertOk()
            ->assertJsonPath('conversation.status', SupportConversationStatusEnum::BOT->value);
    }

    public function test_another_user_cannot_touch_someone_elses_conversation(): void
    {
        $this->bindFakeAi();
        $owner = User::factory()->create(['role' => 'customer']);
        $intruder = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($owner)->create();

        $this->actingAs($intruder);
        $this->getJson("/api/support/conversations/{$conversation->id}")->assertForbidden();
        $this->getJson("/api/support/conversations/{$conversation->id}/messages")->assertForbidden();
        $this->postJson("/api/support/conversations/{$conversation->id}/messages", [
            'content' => 'Hi',
        ])->assertForbidden();

        $this->assertSame(0, SupportMessage::query()->count());
    }

    public function test_guest_cannot_use_the_support_api(): void
    {
        $conversation = SupportConversation::factory()->create();

        $this->postJson('/api/support/conversations')->assertUnauthorized();
        $this->postJson("/api/support/conversations/{$conversation->id}/messages", [
            'content' => 'Hi',
        ])->assertUnauthorized();
    }

    public function test_validation_rejects_empty_and_oversized_messages(): void
    {
        $this->bindFakeAi();
        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create();

        $this->actingAs($user);
        $this->postJson("/api/support/conversations/{$conversation->id}/messages", [
            'content' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors('content');

        $this->postJson("/api/support/conversations/{$conversation->id}/messages", [
            'content' => str_repeat('a', 5001),
        ])->assertUnprocessable()->assertJsonValidationErrors('content');

        $this->assertSame(0, SupportMessage::query()->count());
    }
}
