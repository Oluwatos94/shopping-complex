<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\ChatMessage;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\User;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ChatMessage>
     */
    protected $model = ChatMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<ChatMessage>, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => User::factory(),
            'content' => fake()->sentence(),
            'attachment_path' => null,
            'attachment_type' => null,
            'attachment_name' => null,
            'delivered_at' => null,
            'read_at' => null,
        ];
    }

    /**
     * Set specific conversation for the message.
     */
    public function forConversation(Conversation $conversation): static
    {
        return $this->state(fn (array $attributes) => [
            'conversation_id' => $conversation->id,
        ]);
    }

    /**
     * Set specific sender for the message.
     */
    public function fromSender(User $sender): static
    {
        return $this->state(fn (array $attributes) => [
            'sender_id' => $sender->id,
        ]);
    }

    /**
     * Mark the message as read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Mark the message as delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivered_at' => now(),
        ]);
    }

    /**
     * Add an attachment to the message.
     */
    public function withAttachment(string $type = 'image'): static
    {
        return $this->state(fn (array $attributes) => [
            'attachment_path' => "chat/test/{$type}_".fake()->uuid().'.jpg',
            'attachment_type' => $type,
            'attachment_name' => fake()->word().'.jpg',
        ]);
    }
}
