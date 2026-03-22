<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\User;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Conversation>
     */
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Conversation>, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->create(['role' => 'customer'])->id,
            'vendor_id' => User::factory()->create(['role' => 'vendor'])->id,
            'product_id' => null,
            'last_message_at' => null,
        ];
    }

    /**
     * Set specific customer for the conversation.
     */
    public function forCustomer(User $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Set specific vendor for the conversation.
     */
    public function forVendor(User $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Set specific product for the conversation.
     */
    public function forProduct(int $productId): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $productId,
        ]);
    }

    /**
     * Set the conversation as having recent messages.
     */
    public function withRecentMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_message_at' => now(),
        ]);
    }
}
