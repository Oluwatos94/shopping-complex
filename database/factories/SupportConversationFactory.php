<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Support\Enums\SupportConversationStatusEnum;
use ModulesShoppingComplex\Support\Models\SupportConversation;

/**
 * @extends Factory<SupportConversation>
 */
class SupportConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SupportConversation>
     */
    protected $model = SupportConversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<SupportConversation>, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'customer'])->id,
            'status' => SupportConversationStatusEnum::BOT,
            'last_message_at' => null,
            'escalated_at' => null,
            'agent_id' => null,
        ];
    }

    /**
     * Set a specific user for the thread.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * A guest thread with no associated user.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Escalated and queued for a human agent.
     */
    public function awaitingAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportConversationStatusEnum::AWAITING_AGENT,
            'escalated_at' => now(),
        ]);
    }

    /**
     * Assigned to and handled by a human agent.
     */
    public function withAgent(User $agent): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportConversationStatusEnum::WITH_AGENT,
            'escalated_at' => now(),
            'agent_id' => $agent->id,
        ]);
    }
}
