<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Reviews\Models\Review;
use ModulesShoppingComplex\Reviews\Models\ReviewVote;

/**
 * @extends Factory<ReviewVote>
 */
class ReviewVoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ReviewVote>
     */
    protected $model = ReviewVote::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<ReviewVote>, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'user_id' => User::factory(),
            'is_helpful' => $this->faker->boolean(),
        ];
    }

    /**
     * Set specific review for the vote.
     */
    public function forReview(Review $review): static
    {
        return $this->state(fn () => [
            'review_id' => $review->id,
        ]);
    }

    /**
     * Set specific user for the vote.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set the vote as helpful.
     */
    public function helpful(): static
    {
        return $this->state(fn () => [
            'is_helpful' => true,
        ]);
    }

    /**
     * Set the vote as not helpful.
     */
    public function notHelpful(): static
    {
        return $this->state(fn () => [
            'is_helpful' => false,
        ]);
    }
}
