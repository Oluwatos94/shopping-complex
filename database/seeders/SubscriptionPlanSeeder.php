<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ModulesShoppingComplex\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'price' => 0.00,
                'product_limit' => 10,
                'search_priority' => 0,
                'features' => [
                    'List up to 10 products',
                    'Appear in WhatsApp search results',
                    'Basic vendor profile',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price' => 5000.00,
                'product_limit' => 30,
                'search_priority' => 1,
                'features' => [
                    'List up to 30 products',
                    'Ranked above Free vendors in search',
                    'Basic analytics dashboard',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'price' => 15000.00,
                'product_limit' => 9999,
                'search_priority' => 2,
                'features' => [
                    'Unlimited products',
                    'Top placement in search results',
                    'Full analytics dashboard',
                    'Featured badge on profile',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
