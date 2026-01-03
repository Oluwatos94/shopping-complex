<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use ModulesShoppingComplex\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Clothing & Fashion',
                'slug' => 'clothing-fashion',
                'description' => 'Trendy clothes, shoes, and accessories for men, women, and kids',
            ],
            [
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Latest gadgets, phones, laptops, and electronic accessories',
            ],
            [
                'name' => 'Home & Garden',
                'slug' => 'home-garden',
                'description' => 'Furniture, decor, kitchen items, and gardening supplies',
            ],
            [
                'name' => 'Beauty & Personal Care',
                'slug' => 'beauty-personal-care',
                'description' => 'Cosmetics, skincare, haircare, and wellness products',
            ],
            [
                'name' => 'Sports & Outdoors',
                'slug' => 'sports-outdoors',
                'description' => 'Fitness equipment, outdoor gear, and sporting goods',
            ],
            [
                'name' => 'Books & Stationery',
                'slug' => 'books-stationery',
                'description' => 'Books, office supplies, art materials, and educational items',
            ],
            [
                'name' => 'Toys & Games',
                'slug' => 'toys-games',
                'description' => 'Fun toys, board games, and entertainment for all ages',
            ],
            [
                'name' => 'Food & Beverages',
                'slug' => 'food-beverages',
                'description' => 'Groceries, snacks, drinks, and gourmet food items',
            ],
            [
                'name' => 'Jewelry & Watches',
                'slug' => 'jewelry-watches',
                'description' => 'Fine jewelry, fashion accessories, and timepieces',
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car accessories, tools, and automotive parts',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
