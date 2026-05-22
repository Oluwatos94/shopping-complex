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
            ['id' => 9,  'name' => 'Furniture & Appliances',        'slug' => 'furniture-appliances',        'description' => 'Home furniture, kitchen appliances, and household equipment'],
            ['id' => 10, 'name' => 'Outdoors & Entertainment',      'slug' => 'outdoors-entertainment',      'description' => 'Outdoor gear, sports equipment, games, and entertainment'],
            ['id' => 11, 'name' => 'Automotive & Tools',            'slug' => 'automotive-tools',            'description' => 'Car accessories, spare parts, power tools, and hardware'],
            ['id' => 12, 'name' => 'Art & Gallery',                 'slug' => 'art-gallery',                 'description' => 'Paintings, sculptures, photography, and creative artworks'],
            ['id' => 13, 'name' => 'Restaurant & Catering Service', 'slug' => 'restaurant-catering',         'description' => 'Food vendors, catering services, and dining experiences'],
            ['id' => 14, 'name' => 'Artisan & Handmade Goods',      'slug' => 'artisan-handmade',            'description' => 'Handcrafted products, custom-made items, and local crafts'],
            ['id' => 15, 'name' => 'Footwear',                      'slug' => 'footwear',                    'description' => 'Shoes, sandals, boots, and all types of footwear'],
            ['id' => 16, 'name' => 'Bags & Accessories',            'slug' => 'bags-accessories',            'description' => 'Handbags, backpacks, wallets, belts, and fashion accessories'],
            ['id' => 17, 'name' => 'Baby & Kids Items',             'slug' => 'baby-kids',                   'description' => 'Toys, clothing, feeding supplies, and essentials for babies and children'],
        ];

        $now = now()->toDateTimeString();

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['id' => $category['id']],
                array_merge($category, ['created_at' => $now, 'updated_at' => $now]),
            );
        }

        // Reset the auto-increment so new categories don't collide.
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 18');
    }
}
