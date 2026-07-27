<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Support\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Support\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Support\Models\SupportConversation;
use ModulesShoppingComplex\Support\Models\SupportMessage;
use Tests\TestCase;

class AdminSupportInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function customerMessage(SupportConversation $conversation, string $content): SupportMessage
    {
        return SupportMessage::create([
            'support_conversation_id' => $conversation->id,
            'role' => SupportMessageRoleEnum::USER,
            'sender_id' => $conversation->user_id,
            'content' => $content,
        ]);
    }

    public function test_admin_inbox_lists_only_escalated_and_active_conversations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $awaiting = SupportConversation::factory()->awaitingAgent()->create(['last_message_at' => now()]);
        $withAgent = SupportConversation::factory()->withAgent($admin)->create(['last_message_at' => now()]);

        // These must NOT appear: a bot-only thread and a stale resolved thread.
        SupportConversation::factory()->create(['status' => SupportConversationStatusEnum::BOT]);
        SupportConversation::factory()->create([
            'status' => SupportConversationStatusEnum::RESOLVED,
            'last_message_at' => now()->subDays(30),
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/support/conversations');

        $response->assertOk();
        $ids = array_column($response->json('conversations.data'), 'id');

        $this->assertContains($awaiting->id, $ids);
        $this->assertContains($withAgent->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_inbox_item_carries_the_expected_shape(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Ada Customer']);
        $conversation = SupportConversation::factory()->forUser($customer)->awaitingAgent()->create([
            'last_message_at' => now(),
        ]);
        $this->customerMessage($conversation, 'I need help with my order');

        $response = $this->actingAs($admin)->getJson('/admin/support/conversations');

        $response->assertOk()
            ->assertJsonPath('conversations.data.0.id', $conversation->id)
            ->assertJsonPath('conversations.data.0.user.name', 'Ada Customer')
            ->assertJsonPath('conversations.data.0.status', SupportConversationStatusEnum::AWAITING_AGENT->value)
            ->assertJsonPath('conversations.data.0.unread', true)
            ->assertJsonPath('conversations.data.0.last_message_preview', 'I need help with my order');
    }

    public function test_non_admin_cannot_reach_the_inbox_endpoints(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->awaitingAgent()->create();

        $this->actingAs($customer)->getJson('/admin/support/conversations')->assertForbidden();
        $this->actingAs($customer)->get('/admin/support')->assertForbidden();
        $this->actingAs($customer)
            ->postJson("/admin/support/conversations/{$conversation->id}/read")
            ->assertForbidden();
    }

    public function test_mark_read_clears_unread_and_new_customer_message_reflags_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $conversation = SupportConversation::factory()->awaitingAgent()->create(['last_message_at' => now()]);
        $this->customerMessage($conversation, 'First question');

        $this->actingAs($admin);

        $this->assertTrue($this->firstInboxItem()['unread']);

        $this->postJson("/admin/support/conversations/{$conversation->id}/read")->assertOk();
        $this->assertNotNull($conversation->fresh()->agent_last_read_at);
        $this->assertFalse($this->firstInboxItem()['unread']);

        // A newer customer message must re-flag the thread as unread.
        $this->travel(1)->minutes();
        $this->customerMessage($conversation, 'Actually, one more thing');

        $this->assertTrue($this->firstInboxItem()['unread']);
    }

    /**
     * @return array<string, mixed>
     */
    private function firstInboxItem(): array
    {
        return $this->getJson('/admin/support/conversations')->json('conversations.data.0');
    }

    public function test_customer_sees_the_agent_name_after_an_agent_replies(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Support Sam']);
        $customer = User::factory()->create(['role' => 'customer']);
        $conversation = SupportConversation::factory()->forUser($customer)->awaitingAgent()->create();

        $this->actingAs($admin)
            ->postJson("/api/support/conversations/{$conversation->id}/messages", [
                'content' => 'Hi, this is Jiidaa support.',
            ])->assertCreated();

        $this->actingAs($customer)
            ->getJson("/api/support/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('conversation.agent.name', 'Support Sam');
    }
}
