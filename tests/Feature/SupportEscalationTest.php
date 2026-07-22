<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Event;
use ModulesShoppingComplex\Events\SupportMessageSentEvent;
use ModulesShoppingComplex\Models\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Models\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Models\SupportConversation;
use ModulesShoppingComplex\Models\SupportMessage;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Shared\Contracts\AiChatClient;
use Tests\TestCase;

class SupportEscalationTest extends TestCase
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

    public function test_escalate_flips_status_and_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create();

        $response = $this->actingAs($user)
            ->postJson("/api/support/conversations/{$conversation->id}/escalate");

        $response->assertOk()
            ->assertJsonPath('conversation.status', SupportConversationStatusEnum::AWAITING_AGENT->value);

        $conversation->refresh();
        $this->assertNotNull($conversation->escalated_at);

        $this->assertDatabaseHas(Notification::getTableName(), [
            'user_id' => $admin->id,
            'type' => 'system_alert',
        ]);
    }

    public function test_escalate_is_idempotent(): void
    {
        User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create();

        $this->actingAs($user);
        $this->postJson("/api/support/conversations/{$conversation->id}/escalate")->assertOk();
        $this->postJson("/api/support/conversations/{$conversation->id}/escalate")->assertOk();

        $this->assertSame(1, Notification::query()->count());
    }

    public function test_only_the_owner_can_escalate(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $intruder = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create();

        $this->actingAs($intruder)
            ->postJson("/api/support/conversations/{$conversation->id}/escalate")
            ->assertForbidden();

        $this->assertSame(SupportConversationStatusEnum::BOT, $conversation->fresh()->status);
    }

    public function test_bot_request_human_tool_auto_escalates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $fake = $this->bindFakeAi();
        $fake->queueResponses([
            [
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'tool_1', 'name' => 'request_human', 'input' => []],
                ],
            ],
            [
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'A human agent will be with you shortly.']],
            ],
        ]);

        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create();

        $response = $this->actingAs($user)
            ->postJson("/api/support/conversations/{$conversation->id}/messages", [
                'content' => 'I want to talk to a real person',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message.content', 'A human agent will be with you shortly.');

        $conversation->refresh();
        $this->assertSame(SupportConversationStatusEnum::AWAITING_AGENT, $conversation->status);
        $this->assertNotNull($conversation->escalated_at);

        $this->assertDatabaseHas(Notification::getTableName(), [
            'user_id' => $admin->id,
            'type' => 'system_alert',
        ]);
    }

    public function test_agent_reply_claims_conversation_and_broadcasts(): void
    {
        Event::fake([SupportMessageSentEvent::class]);

        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create([
            'status' => SupportConversationStatusEnum::AWAITING_AGENT,
            'escalated_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/support/conversations/{$conversation->id}/messages", [
                'content' => 'Hi, this is Jiidaa support. How can I help?',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message.role', SupportMessageRoleEnum::AGENT->value)
            ->assertJsonPath('message.sender_id', $admin->id);

        $conversation->refresh();
        $this->assertSame(SupportConversationStatusEnum::WITH_AGENT, $conversation->status);
        $this->assertSame($admin->id, $conversation->agent_id);

        Event::assertDispatched(SupportMessageSentEvent::class, function (SupportMessageSentEvent $event) use ($conversation) {
            return $event->message->support_conversation_id === $conversation->id
                && $event->message->role === SupportMessageRoleEnum::AGENT;
        });
    }

    public function test_non_admin_cannot_post_as_agent(): void
    {
        $this->bindFakeAi();
        $user = User::factory()->create(['role' => 'customer']);
        $vendor = User::factory()->create(['role' => 'vendor']);
        $conversation = SupportConversation::factory()->forUser($user)->create([
            'status' => SupportConversationStatusEnum::AWAITING_AGENT,
            'escalated_at' => now(),
        ]);

        $this->actingAs($vendor)
            ->postJson("/api/support/conversations/{$conversation->id}/messages", [
                'content' => 'Let me handle this',
            ])
            ->assertForbidden();

        $this->assertSame(0, SupportMessage::query()->count());
    }

    public function test_bot_keeps_replying_while_awaiting_agent(): void
    {
        $this->bindFakeAi('An agent is on the way — meanwhile, how can I help?');
        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create([
            'status' => SupportConversationStatusEnum::AWAITING_AGENT,
            'escalated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/support/conversations/{$conversation->id}/messages", [
                'content' => 'I need a product',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message.role', SupportMessageRoleEnum::ASSISTANT->value)
            ->assertJsonPath('message.content', 'An agent is on the way — meanwhile, how can I help?');
    }

    public function test_bot_reply_during_awaiting_agent_broadcasts_both_messages(): void
    {
        Event::fake([SupportMessageSentEvent::class]);

        $this->bindFakeAi('An agent is on the way — meanwhile, how can I help?');
        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create([
            'status' => SupportConversationStatusEnum::AWAITING_AGENT,
            'escalated_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson("/api/support/conversations/{$conversation->id}/messages", [
                'content' => 'Some extra details for the agent',
            ])
            ->assertCreated();

        Event::assertDispatched(SupportMessageSentEvent::class, function (SupportMessageSentEvent $event) {
            return $event->message->role === SupportMessageRoleEnum::USER;
        });
        Event::assertDispatched(SupportMessageSentEvent::class, function (SupportMessageSentEvent $event) {
            return $event->message->role === SupportMessageRoleEnum::ASSISTANT;
        });
    }

    public function test_bot_hands_off_once_an_agent_joins(): void
    {
        $fake = $this->bindFakeAi();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create([
            'status' => SupportConversationStatusEnum::WITH_AGENT,
            'escalated_at' => now(),
            'agent_id' => $admin->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/support/conversations/{$conversation->id}/messages", [
                'content' => 'Hello? Anyone there?',
            ]);

        $response->assertCreated()
            ->assertJsonPath('message.role', SupportMessageRoleEnum::USER->value);

        $this->assertSame([], $fake->payloads);
        $this->assertSame(1, SupportMessage::query()->count());
    }

    public function test_owner_and_admin_can_resolve(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'customer']);
        $own = SupportConversation::factory()->forUser($user)->create();
        $handled = SupportConversation::factory()->create([
            'status' => SupportConversationStatusEnum::WITH_AGENT,
            'agent_id' => $admin->id,
        ]);

        $this->actingAs($user)
            ->postJson("/api/support/conversations/{$own->id}/resolve")
            ->assertOk()
            ->assertJsonPath('conversation.status', SupportConversationStatusEnum::RESOLVED->value);

        $this->actingAs($admin)
            ->postJson("/api/support/conversations/{$handled->id}/resolve")
            ->assertOk();

        $this->assertSame(SupportConversationStatusEnum::RESOLVED, $handled->fresh()->status);
    }

    public function test_other_customer_cannot_resolve(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $intruder = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($user)->create();

        $this->actingAs($intruder)
            ->postJson("/api/support/conversations/{$conversation->id}/resolve")
            ->assertForbidden();
    }
}
