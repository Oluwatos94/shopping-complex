<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\Enums\ReviewStatusEnum;
use ModulesShoppingComplex\Models\Review;
use ModulesShoppingComplex\Models\User;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Review>
     */
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Review>, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory()->create(['role' => 'customer'])->id,
            'vendor_id' => User::factory()->create(['role' => 'vendor'])->id,
            'conversation_id' => null,
            'rating' => $this->faker->numberBetween(1, 5),
            'title' => $this->faker->optional(0.7)->sentence(4),
            'comment' => $this->faker->optional(0.8)->paragraph(),
            'status' => ReviewStatusEnum::PENDING,
            'moderated_by' => null,
            'moderated_at' => null,
            'moderation_notes' => null,
            'helpful_count' => 0,
            'not_helpful_count' => 0,
            'vendor_response' => null,
            'vendor_responded_at' => null,
        ];
    }

    /**
     * Set specific customer for the review.
     */
    public function forCustomer(User $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Set specific vendor for the review.
     */
    public function forVendor(User $vendor): static
    {
        return $this->state(fn () => [
            'vendor_id' => $vendor->id,
        ]);
    }

    /**
     * Set specific conversation for the review.
     */
    public function forConversation(Conversation $conversation): static
    {
        return $this->state(fn () => [
            'conversation_id' => $conversation->id,
            'customer_id' => $conversation->customer_id,
            'vendor_id' => $conversation->vendor_id,
        ]);
    }

    /**
     * Set the review as approved.
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ReviewStatusEnum::APPROVED,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Set the review as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ReviewStatusEnum::REJECTED,
            'moderated_at' => now(),
        ]);
    }

    /**
     * Set the review as pending.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ReviewStatusEnum::PENDING,
        ]);
    }

    /**
     * Set a specific rating.
     */
    public function withRating(int $rating): static
    {
        return $this->state(fn () => [
            'rating' => $rating,
        ]);
    }

    /**
     * Set the review with a vendor response.
     */
    public function withVendorResponse(): static
    {
        return $this->state(fn () => [
            'vendor_response' => $this->faker->paragraph(),
            'vendor_responded_at' => now(),
        ]);
    }

    /**
     * Set helpful votes count.
     */
    public function withHelpfulVotes(int $count): static
    {
        return $this->state(fn () => [
            'helpful_count' => $count,
        ]);
    }

    /**
     * Set not helpful votes count.
     */
    public function withNotHelpfulVotes(int $count): static
    {
        return $this->state(fn () => [
            'not_helpful_count' => $count,
        ]);
    }
}
