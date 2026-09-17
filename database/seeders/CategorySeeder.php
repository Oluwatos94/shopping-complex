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
        // lead_coin_cost prices a lead in this trade; omitted means the 5-coin default.
        $categories = [
            ['id' => 1, 'name' => 'Services',                 'slug' => 'services',               'lead_coin_cost' => 20, 'description' => 'Professional and home services from trusted local providers'],
            ['id' => 2, 'name' => 'Books & Education',        'slug' => 'books-education',         'description' => 'Books, stationery, courses, and educational materials'],
            ['id' => 4, 'name' => 'Groceries & Food',         'slug' => 'groceries-food',          'description' => 'Fresh produce, packaged goods, snacks, and beverages'],
            ['id' => 5, 'name' => 'Health & Beauty',          'slug' => 'health-beauty',           'description' => 'Cosmetics, skincare, haircare, and wellness products'],
            ['id' => 6, 'name' => 'Accessories & Lifestyle',  'slug' => 'accessories-lifestyle',   'description' => 'Jewellery, bags, watches, and everyday lifestyle items'],
            ['id' => 7, 'name' => 'Electronics & Repairs',    'slug' => 'electronics-repairs',     'lead_coin_cost' => 10, 'description' => 'Gadgets, phones, laptops, and repair services'],
            ['id' => 8, 'name' => 'Fashion & Clothing',       'slug' => 'fashion-clothing',        'description' => 'Clothes, shoes, and accessories for men, women, and kids'],
            ['id' => 9,  'name' => 'Furniture & Appliances',        'slug' => 'furniture-appliances',        'lead_coin_cost' => 10, 'description' => 'Home furniture, kitchen appliances, and household equipment'],
            ['id' => 10, 'name' => 'Outdoors & Entertainment',      'slug' => 'outdoors-entertainment',      'lead_coin_cost' => 10, 'description' => 'Outdoor gear, sports equipment, games, and entertainment'],
            ['id' => 11, 'name' => 'Automotive & Tools',            'slug' => 'automotive-tools',            'lead_coin_cost' => 20, 'description' => 'Car accessories, spare parts, power tools, and hardware'],
            ['id' => 12, 'name' => 'Art & Gallery',                 'slug' => 'art-gallery',                 'lead_coin_cost' => 10, 'description' => 'Paintings, sculptures, photography, and creative artworks'],
            ['id' => 13, 'name' => 'Restaurant & Catering Service', 'slug' => 'restaurant-catering',         'description' => 'Food vendors, catering services, and dining experiences'],
            ['id' => 14, 'name' => 'Artisan & Handmade Goods',      'slug' => 'artisan-handmade',            'description' => 'Handcrafted products, custom-made items, and local crafts'],
            ['id' => 15, 'name' => 'Footwear',                      'slug' => 'footwear',                    'description' => 'Shoes, sandals, boots, and all types of footwear'],
            ['id' => 16, 'name' => 'Bags & Accessories',            'slug' => 'bags-accessories',            'description' => 'Handbags, backpacks, wallets, belts, and fashion accessories'],
            ['id' => 17, 'name' => 'Baby & Kids Items',             'slug' => 'baby-kids',                   'description' => 'Toys, clothing, feeding supplies, and essentials for babies and children'],
            ['id' => 18, 'name' => 'Real Estate & Property',        'slug' => 'real-estate-property',        'lead_coin_cost' => 30, 'description' => 'Property sales, rentals, and real estate services'],
        ];

        $now = now()->toDateTimeString();

        foreach ($categories as $category) {
            $tier = $category['lead_coin_cost'] ?? 5;
            $metadata = ['name' => $category['name'], 'slug' => $category['slug'], 'description' => $category['description']];

            if (DB::table('categories')->where('id', $category['id'])->exists()) {
                DB::table('categories')->where('id', $category['id'])->update($metadata + ['updated_at' => $now]);

                continue;
            }

            DB::table('categories')->insert(
                $metadata + ['id' => $category['id'], 'lead_coin_cost' => $tier, 'created_at' => $now, 'updated_at' => $now],
            );
        }

        // Reset the auto-increment so new categories don't collide.
        DB::statement('ALTER TABLE categories AUTO_INCREMENT = 19');
    }
}
