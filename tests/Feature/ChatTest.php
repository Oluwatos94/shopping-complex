<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use ModulesShoppingComplex\Events\MessageReadEvent;
use ModulesShoppingComplex\Events\MessageSentEvent;
use ModulesShoppingComplex\Events\TypingIndicatorEvent;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Models\ChatMessage;
use ModulesShoppingComplex\Models\Conversation;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;

    protected User $customer;

    protected User $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }

    // ==================== Conversation Tests ====================

    public function test_customer_can_start_conversation_with_vendor(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/chat/conversations', [
                'vendor_id' => $this->vendor->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'conversation' => ['id', 'customer_id', 'vendor_id'],
                'message',
            ]);

        $this->assertDatabaseHas('conversations', [
            'customer_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
        ]);
    }

    public function test_vendor_can_start_conversation_with_customer(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson('/api/chat/conversations', [
                'customer_id' => $this->customer->id,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('conversations', [
            'customer_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
        ]);
    }

    public function test_starting_existing_conversation_returns_same_conversation(): void
    {
        // Create first conversation
        $response1 = $this->actingAs($this->customer)
            ->postJson('/api/chat/conversations', [
                'vendor_id' => $this->vendor->id,
            ]);

        $conversationId1 = $response1->json('conversation.id');

        // Try to create another with same participants
        $response2 = $this->actingAs($this->customer)
            ->postJson('/api/chat/conversations', [
                'vendor_id' => $this->vendor->id,
            ]);

        $conversationId2 = $response2->json('conversation.id');

        $this->assertEquals($conversationId1, $conversationId2);
        $this->assertEquals(1, Conversation::count());
    }

    public function test_customer_cannot_start_conversation_with_another_customer(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/chat/conversations', [
                'vendor_id' => $this->otherCustomer->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_list_their_conversations(): void
    {
        // Create conversations
        Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->withRecentMessage()
            ->create();

        $response = $this->actingAs($this->customer)
            ->getJson('/api/chat/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'conversations',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(1, $response->json('conversations'));
    }

    public function test_user_cannot_access_other_users_conversation(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->otherCustomer)
            ->getJson("/api/chat/conversations/{$conversation->id}");

        $response->assertStatus(403);
    }

    // ==================== Message Tests ====================

    public function test_user_can_send_message_in_conversation(): void
    {
        Event::fake([MessageSentEvent::class]);

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Hello, I am interested in your product!',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message' => ['id', 'conversation_id', 'sender_id', 'content'],
                'success',
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->customer->id,
            'content' => 'Hello, I am interested in your product!',
        ]);

        Event::assertDispatched(MessageSentEvent::class);
    }

    public function test_message_sent_event_broadcasts_to_recipient(): void
    {
        Event::fake([MessageSentEvent::class]);

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Test message',
            ]);

        Event::assertDispatched(MessageSentEvent::class, function ($event) {
            return $event->recipient->id === $this->vendor->id;
        });
    }

    public function test_user_can_get_messages_for_conversation(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        ChatMessage::factory()
            ->forConversation($conversation)
            ->fromSender($this->customer)
            ->count(5)
            ->create();

        $response = $this->actingAs($this->customer)
            ->getJson("/api/chat/conversations/{$conversation->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'messages',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(5, $response->json('messages'));
    }

    public function test_messages_appear_instantly_via_broadcast(): void
    {
        Event::fake([MessageSentEvent::class]);

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Instant message',
            ]);

        // Verify event was dispatched (which would broadcast instantly)
        Event::assertDispatched(MessageSentEvent::class, function ($event) {
            return $event->message->content === 'Instant message';
        });
    }

    // ==================== Read Receipt Tests ====================

    public function test_user_can_mark_messages_as_read(): void
    {
        Event::fake([MessageReadEvent::class]);

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        // Vendor sends messages to customer
        ChatMessage::factory()
            ->forConversation($conversation)
            ->fromSender($this->vendor)
            ->count(3)
            ->create();

        $response = $this->actingAs($this->customer)
            ->patchJson("/api/chat/conversations/{$conversation->id}/messages/read");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Messages marked as read',
                'count' => 3,
            ]);

        // Verify all messages are now read
        $this->assertEquals(0, ChatMessage::where('conversation_id', $conversation->id)
            ->whereNull('read_at')
            ->count());

        Event::assertDispatched(MessageReadEvent::class);
    }

    public function test_read_receipts_update_correctly(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $message = ChatMessage::factory()
            ->forConversation($conversation)
            ->fromSender($this->vendor)
            ->create();

        $this->assertNull($message->read_at);

        $this->actingAs($this->customer)
            ->patchJson("/api/chat/conversations/{$conversation->id}/messages/read");

        $message->refresh();
        $this->assertNotNull($message->read_at);
    }

    public function test_user_cannot_mark_own_messages_as_read(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        // Customer sends messages
        ChatMessage::factory()
            ->forConversation($conversation)
            ->fromSender($this->customer)
            ->count(3)
            ->create();

        $response = $this->actingAs($this->customer)
            ->patchJson("/api/chat/conversations/{$conversation->id}/messages/read");

        // Count should be 0 as they're the sender
        $response->assertJson(['count' => 0]);
    }

    // ==================== Typing Indicator Tests ====================

    public function test_user_can_send_typing_indicator(): void
    {
        Event::fake([TypingIndicatorEvent::class]);

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/typing");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Typing indicator sent']);

        Event::assertDispatched(TypingIndicatorEvent::class, function ($event) {
            return $event->typer->id === $this->customer->id
                && $event->recipient->id === $this->vendor->id;
        });
    }

    public function test_typing_indicators_work(): void
    {
        Event::fake([TypingIndicatorEvent::class]);

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/typing");

        Event::assertDispatched(TypingIndicatorEvent::class, function ($event) use ($conversation) {
            return $event->conversation->id === $conversation->id;
        });
    }

    // ==================== File Attachment Tests ====================

    public function test_user_can_send_message_with_attachment(): void
    {
        Event::fake([MessageSentEvent::class]);
        Storage::fake('public');

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $file = UploadedFile::fake()->image('product-photo.jpg');

        $response = $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Here is a photo',
                'attachment' => $file,
            ]);

        $response->assertStatus(201);

        $message = ChatMessage::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($message->attachment_path);
        $this->assertEquals('image', $message->attachment_type);
        $this->assertEquals('product-photo.jpg', $message->attachment_name);
    }

    public function test_file_attachments_supported(): void
    {
        Event::fake([MessageSentEvent::class]);
        Storage::fake('public');

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        // Test different file types
        $imageFile = UploadedFile::fake()->image('photo.png');
        $pdfFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        // Send image
        $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Image',
                'attachment' => $imageFile,
            ])
            ->assertStatus(201);

        // Send PDF
        $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Document',
                'attachment' => $pdfFile,
            ])
            ->assertStatus(201);

        $this->assertEquals(2, ChatMessage::where('conversation_id', $conversation->id)->count());
    }

    public function test_attachment_validation_rejects_invalid_file_types(): void
    {
        Storage::fake('public');

        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $file = UploadedFile::fake()->create('malicious.exe', 500, 'application/x-msdownload');

        $response = $this->actingAs($this->customer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Bad file',
                'attachment' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('attachment');
    }

    // ==================== Conversation History Tests ====================

    public function test_conversation_history_loads_correctly(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        // Create messages with specific timestamps
        for ($i = 1; $i <= 10; $i++) {
            ChatMessage::factory()
                ->forConversation($conversation)
                ->fromSender($i % 2 === 0 ? $this->customer : $this->vendor)
                ->create(['created_at' => now()->subMinutes(10 - $i)]);
        }

        $response = $this->actingAs($this->customer)
            ->getJson("/api/chat/conversations/{$conversation->id}/messages");

        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('meta.total'));
    }

    public function test_messages_are_paginated(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        ChatMessage::factory()
            ->forConversation($conversation)
            ->fromSender($this->customer)
            ->count(100)
            ->create();

        $response = $this->actingAs($this->customer)
            ->getJson("/api/chat/conversations/{$conversation->id}/messages?per_page=20");

        $response->assertStatus(200);
        $this->assertEquals(20, count($response->json('messages')));
        $this->assertEquals(5, $response->json('meta.last_page'));
    }

    // ==================== Poll Messages Test ====================

    public function test_user_can_poll_for_new_messages(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $oldMessage = ChatMessage::factory()
            ->forConversation($conversation)
            ->fromSender($this->vendor)
            ->create();

        // Create new message after the first one
        $newMessage = ChatMessage::factory()
            ->forConversation($conversation)
            ->fromSender($this->vendor)
            ->create();

        $response = $this->actingAs($this->customer)
            ->getJson("/api/chat/conversations/{$conversation->id}/messages/poll?after_message_id={$oldMessage->id}");

        $response->assertStatus(200)
            ->assertJson(['has_new' => true]);

        $this->assertCount(1, $response->json('messages'));
        $this->assertEquals($newMessage->id, $response->json('messages.0.id'));
    }

    // ==================== Unread Count Test ====================

    public function test_user_can_get_total_unread_count(): void
    {
        // Create two conversations with unread messages
        $conversation1 = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $conversation2 = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($otherVendor)
            ->create();

        ChatMessage::factory()
            ->forConversation($conversation1)
            ->fromSender($this->vendor)
            ->count(3)
            ->create();

        ChatMessage::factory()
            ->forConversation($conversation2)
            ->fromSender($otherVendor)
            ->count(2)
            ->create();

        $response = $this->actingAs($this->customer)
            ->getJson('/api/chat/unread-count');

        $response->assertStatus(200)
            ->assertJson(['unread_count' => 5]);
    }

    // ==================== Authorization Tests ====================

    public function test_unauthenticated_user_cannot_access_chat(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $this->getJson('/api/chat/conversations')
            ->assertStatus(401);

        $this->postJson('/api/chat/conversations', ['vendor_id' => $this->vendor->id])
            ->assertStatus(401);

        $this->getJson("/api/chat/conversations/{$conversation->id}/messages")
            ->assertStatus(401);

        $this->postJson("/api/chat/conversations/{$conversation->id}/messages", ['content' => 'Test'])
            ->assertStatus(401);
    }

    public function test_user_cannot_send_message_to_conversation_they_are_not_part_of(): void
    {
        $conversation = Conversation::factory()
            ->forCustomer($this->customer)
            ->forVendor($this->vendor)
            ->create();

        $response = $this->actingAs($this->otherCustomer)
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'content' => 'Unauthorized message',
            ]);

        $response->assertStatus(403);
    }
}
