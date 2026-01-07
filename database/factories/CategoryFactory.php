<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\ModulesShoppingComplex\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\ModulesShoppingComplex\Models\Category>
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * {@inheritdoc}
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
        ];
    }
}
