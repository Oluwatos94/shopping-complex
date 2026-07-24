<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Notifications\Models\Notification;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ModulesShoppingComplex\Notifications\Models\Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\ModulesShoppingComplex\Notifications\Models\Notification>
     */
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<Notification>, mixed>
     */
    public function definition(): array
    {
        $types = array_keys(config('notifications.types', [
            'message_received' => [],
            'vendor_contact_request' => [],
            'review_received' => [],
            'system_alert' => [],
        ]));

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement($types),
            'message' => fake()->sentence(),
            'data' => [],
            'read_at' => null,
            'group_key' => null,
            'is_grouped' => false,
            'group_count' => 1,
        ];
    }

    /**
     * Indicate that the notification has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    /**
     * Indicate that the notification is grouped.
     */
    public function grouped(int $count = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'is_grouped' => true,
            'group_count' => $count,
            'group_key' => 'group_'.fake()->uuid(),
        ]);
    }

    /**
     * Set the notification type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Set notification as message received type.
     */
    public function messageReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'message_received',
            'message' => fake()->name().' sent you a message',
            'data' => [
                'sender_id' => fake()->randomNumber(5),
                'sender_name' => fake()->name(),
                'message_preview' => fake()->sentence(),
            ],
        ]);
    }

    /**
     * Set notification as vendor contact request type.
     */
    public function vendorContactRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'vendor_contact_request',
            'message' => fake()->name().' wants to contact you',
            'data' => [
                'customer_id' => fake()->randomNumber(5),
                'customer_name' => fake()->name(),
                'product_name' => fake()->words(3, true),
            ],
        ]);
    }

    /**
     * Set notification as system alert type.
     */
    public function systemAlert(string $level = 'info'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system_alert',
            'message' => fake()->sentence(),
            'data' => [
                'alert_level' => $level,
            ],
        ]);
    }
}
