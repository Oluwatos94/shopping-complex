<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // These IDs match the image map used on the frontend (cat1.jpg … cat8.jpg).
        $categories = [
            ['id' => 1, 'name' => 'Services',                 'slug' => 'services',               'description' => 'Professional and home services from trusted local providers'],
            ['id' => 2, 'name' => 'Books & Education',        'slug' => 'books-education',         'description' => 'Books, stationery, courses, and educational materials'],
            ['id' => 4, 'name' => 'Groceries & Food',         'slug' => 'groceries-food',          'description' => 'Fresh produce, packaged goods, snacks, and beverages'],
            ['id' => 5, 'name' => 'Health & Beauty',          'slug' => 'health-beauty',           'description' => 'Cosmetics, skincare, haircare, and wellness products'],
            ['id' => 6, 'name' => 'Accessories & Lifestyle',  'slug' => 'accessories-lifestyle',   'description' => 'Jewellery, bags, watches, and everyday lifestyle items'],
            ['id' => 7, 'name' => 'Electronics & Repairs',    'slug' => 'electronics-repairs',     'description' => 'Gadgets, phones, laptops, and repair services'],
            ['id' => 8, 'name' => 'Fashion & Clothing',       'slug' => 'fashion-clothing',        'description' => 'Clothes, shoes, and accessories for men, women, and kids'],
        ];

        $now = now()->toDateTimeString();

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['id' => $category['id']],
                array_merge($category, ['created_at' => $now, 'updated_at' => $now]),
            );
        }

        // Reset the auto-increment so new categories don't collide.
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 9');
    }
}
