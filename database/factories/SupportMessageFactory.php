<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Support\Enums\SupportMessageRoleEnum;
use ModulesShoppingComplex\Support\Models\SupportConversation;
use ModulesShoppingComplex\Support\Models\SupportMessage;

/**
 * @extends Factory<SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportMessage>
     */
    protected $model = SupportMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<SupportMessage>, mixed>
     */
    public function definition(): array
    {
        return [
            'support_conversation_id' => SupportConversation::factory(),
            'role' => SupportMessageRoleEnum::USER,
            'sender_id' => User::factory(),
            'content' => fake()->sentence(),
            'read_at' => null,
        ];
    }

    /**
     * Set a specific thread for the message.
     */
    public function forConversation(SupportConversation $conversation): static
    {
        return $this->state(fn (array $attributes) => [
            'support_conversation_id' => $conversation->id,
        ]);
    }

    /**
     * A message from the customer.
     */
    public function fromUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => SupportMessageRoleEnum::USER,
            'sender_id' => $user->id,
        ]);
    }

    /**
     * A message from the Gemini assistant (no sender).
     */
    public function fromAssistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => SupportMessageRoleEnum::ASSISTANT,
            'sender_id' => null,
        ]);
    }

    /**
     * A message from a human support agent.
     */
    public function fromAgent(User $agent): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => SupportMessageRoleEnum::AGENT,
            'sender_id' => $agent->id,
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
}
