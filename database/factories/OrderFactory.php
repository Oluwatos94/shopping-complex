<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\Order;
use ModulesShoppingComplex\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ModulesShoppingComplex\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\ModulesShoppingComplex\Models\Order>
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * {@inheritdoc}
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(['role' => 'customer']),
            'vendor_id' => User::factory(['role' => 'vendor']),
            'status' => 'pending',
            'total' => fake()->randomFloat(2, 50, 5000),
        ];
    }
}
