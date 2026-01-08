<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\Order;
use ModulesShoppingComplex\Models\OrderItem;
use ModulesShoppingComplex\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ModulesShoppingComplex\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\ModulesShoppingComplex\Models\OrderItem>
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * {@inheritdoc}
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'price' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
