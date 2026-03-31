<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\ProductView;
use ModulesShoppingComplex\Models\User;

/**
 * @extends Factory<ProductView>
 */
class ProductViewFactory extends Factory
{
    /**
     * @var class-string<ProductView>
     */
    protected $model = ProductView::class;

    /**
     * @return array<model-property<ProductView>, mixed>
     */
    public function definition(): array
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        return [
            'product_id' => Product::factory()->create(['vendor_id' => $vendor->id])->id,
            'vendor_id' => $vendor->id,
            'viewer_id' => User::factory()->create(['role' => 'customer'])->id,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function forVendor(User $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
        ]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'vendor_id' => $product->vendor_id,
        ]);
    }

    public function forViewer(User $viewer): static
    {
        return $this->state(fn (array $attributes) => [
            'viewer_id' => $viewer->id,
        ]);
    }
}
