<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use ModulesShoppingComplex\Analytics\Models\ProfileView;
use ModulesShoppingComplex\Identity\Models\User;

/**
 * @extends Factory<ProfileView>
 */
class ProfileViewFactory extends Factory
{
    /**
     * @var class-string<ProfileView>
     */
    protected $model = ProfileView::class;

    /**
     * @return array<model-property<ProfileView>, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => User::factory()->create(['role' => 'vendor'])->id,
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

    public function forViewer(User $viewer): static
    {
        return $this->state(fn (array $attributes) => [
            'viewer_id' => $viewer->id,
        ]);
    }

    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'viewer_id' => null,
        ]);
    }
}
