<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Models\Category;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create vendor users
        $vendors = [
            [
                'name' => 'Fashion Hub',
                'email' => 'fashion@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'business_name' => 'Fashion Hub',
                'bio' => 'Your one-stop shop for trendy clothing and accessories',
            ],
            [
                'name' => 'Tech Store',
                'email' => 'tech@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'business_name' => 'Tech Store',
                'bio' => 'Latest electronics and gadgets at competitive prices',
            ],
            [
                'name' => 'Home Decor Plus',
                'email' => 'homedecor@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'business_name' => 'Home Decor Plus',
                'bio' => 'Beautiful home furnishings and garden supplies',
            ],
            [
                'name' => 'Beauty Haven',
                'email' => 'beauty@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'business_name' => 'Beauty Haven',
                'bio' => 'Premium beauty and personal care products',
            ],
            [
                'name' => 'Sports Central',
                'email' => 'sports@example.com',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'business_name' => 'Sports Central',
                'bio' => 'All your sporting needs in one place',
            ],
        ];

        $createdVendors = [];
        foreach ($vendors as $vendorData) {
            $createdVendors[] = User::create($vendorData);
        }

        // Get all categories
        $categories = Category::all();

        // Sample products for each category
        $productsByCategory = [
            'clothing-fashion' => [
                ['name' => 'Classic Cotton T-Shirt', 'price' => 19.99, 'stock' => 100, 'description' => 'Comfortable 100% cotton t-shirt available in multiple colors'],
                ['name' => 'Denim Jeans', 'price' => 49.99, 'stock' => 75, 'description' => 'Stylish slim-fit denim jeans with modern cut'],
                ['name' => 'Summer Dress', 'price' => 39.99, 'stock' => 50, 'description' => 'Lightweight floral summer dress perfect for warm weather'],
                ['name' => 'Leather Jacket', 'price' => 129.99, 'stock' => 30, 'description' => 'Genuine leather jacket with classic design'],
                ['name' => 'Running Shoes', 'price' => 79.99, 'stock' => 60, 'description' => 'Lightweight running shoes with excellent cushioning'],
                ['name' => 'Winter Coat', 'price' => 149.99, 'stock' => 40, 'description' => 'Warm winter coat with water-resistant fabric'],
                ['name' => 'Casual Sneakers', 'price' => 59.99, 'stock' => 80, 'description' => 'Comfortable casual sneakers for everyday wear'],
                ['name' => 'Polo Shirt', 'price' => 34.99, 'stock' => 90, 'description' => 'Classic polo shirt made from premium cotton'],
            ],
            'electronics' => [
                ['name' => 'Wireless Earbuds', 'price' => 89.99, 'stock' => 120, 'description' => 'Premium wireless earbuds with noise cancellation'],
                ['name' => 'Smartphone Case', 'price' => 24.99, 'stock' => 200, 'description' => 'Durable protective case for smartphones'],
                ['name' => 'Portable Charger', 'price' => 39.99, 'stock' => 150, 'description' => '10000mAh portable power bank for all devices'],
                ['name' => 'Bluetooth Speaker', 'price' => 59.99, 'stock' => 85, 'description' => 'Waterproof Bluetooth speaker with 12-hour battery'],
                ['name' => 'Laptop Stand', 'price' => 34.99, 'stock' => 100, 'description' => 'Ergonomic adjustable laptop stand'],
                ['name' => 'USB-C Cable', 'price' => 14.99, 'stock' => 300, 'description' => 'Fast charging USB-C cable (6ft)'],
            ],
            'home-garden' => [
                ['name' => 'Throw Pillow Set', 'price' => 29.99, 'stock' => 75, 'description' => 'Set of 4 decorative throw pillows'],
                ['name' => 'Wall Art Print', 'price' => 44.99, 'stock' => 50, 'description' => 'Modern abstract wall art print with frame'],
                ['name' => 'Garden Tool Set', 'price' => 54.99, 'stock' => 40, 'description' => 'Complete 10-piece garden tool set'],
                ['name' => 'Table Lamp', 'price' => 39.99, 'stock' => 60, 'description' => 'Modern LED table lamp with touch control'],
                ['name' => 'Kitchen Knife Set', 'price' => 79.99, 'stock' => 45, 'description' => 'Professional 8-piece kitchen knife set'],
            ],
            'beauty-personal-care' => [
                ['name' => 'Face Moisturizer', 'price' => 34.99, 'stock' => 100, 'description' => 'Hydrating face moisturizer for all skin types'],
                ['name' => 'Makeup Brush Set', 'price' => 49.99, 'stock' => 75, 'description' => 'Professional 12-piece makeup brush set'],
                ['name' => 'Hair Styling Tool', 'price' => 89.99, 'stock' => 50, 'description' => '3-in-1 hair styling tool with heat protection'],
                ['name' => 'Perfume', 'price' => 69.99, 'stock' => 60, 'description' => 'Elegant floral fragrance (50ml)'],
                ['name' => 'Skincare Set', 'price' => 99.99, 'stock' => 40, 'description' => 'Complete 5-step skincare routine set'],
            ],
            'sports-outdoors' => [
                ['name' => 'Yoga Mat', 'price' => 29.99, 'stock' => 100, 'description' => 'Non-slip yoga mat with carrying strap'],
                ['name' => 'Dumbbell Set', 'price' => 79.99, 'stock' => 50, 'description' => 'Adjustable dumbbell set (5-25 lbs)'],
                ['name' => 'Water Bottle', 'price' => 19.99, 'stock' => 150, 'description' => 'Insulated stainless steel water bottle (32oz)'],
                ['name' => 'Resistance Bands', 'price' => 24.99, 'stock' => 120, 'description' => 'Set of 5 resistance bands with different strengths'],
                ['name' => 'Camping Tent', 'price' => 149.99, 'stock' => 30, 'description' => '4-person waterproof camping tent'],
            ],
            'books-stationery' => [
                ['name' => 'Notebook Set', 'price' => 14.99, 'stock' => 200, 'description' => 'Pack of 3 premium lined notebooks'],
                ['name' => 'Pen Collection', 'price' => 19.99, 'stock' => 150, 'description' => 'Set of 12 colorful gel pens'],
                ['name' => 'Desk Organizer', 'price' => 29.99, 'stock' => 80, 'description' => 'Bamboo desk organizer with multiple compartments'],
                ['name' => 'Art Supplies Kit', 'price' => 59.99, 'stock' => 60, 'description' => 'Complete drawing and sketching supplies kit'],
            ],
            'toys-games' => [
                ['name' => 'Building Blocks Set', 'price' => 39.99, 'stock' => 100, 'description' => '500-piece building blocks set'],
                ['name' => 'Board Game', 'price' => 29.99, 'stock' => 75, 'description' => 'Family-friendly strategy board game'],
                ['name' => 'Puzzle Set', 'price' => 24.99, 'stock' => 90, 'description' => '1000-piece jigsaw puzzle'],
                ['name' => 'Action Figure', 'price' => 19.99, 'stock' => 120, 'description' => 'Collectible action figure with accessories'],
            ],
            'food-beverages' => [
                ['name' => 'Organic Coffee Beans', 'price' => 24.99, 'stock' => 100, 'description' => 'Premium organic coffee beans (1lb bag)'],
                ['name' => 'Green Tea Set', 'price' => 19.99, 'stock' => 150, 'description' => 'Assorted green tea variety pack'],
                ['name' => 'Gourmet Chocolate', 'price' => 34.99, 'stock' => 80, 'description' => 'Belgian dark chocolate gift box'],
                ['name' => 'Olive Oil', 'price' => 29.99, 'stock' => 70, 'description' => 'Extra virgin olive oil (500ml)'],
            ],
            'jewelry-watches' => [
                ['name' => 'Silver Necklace', 'price' => 79.99, 'stock' => 50, 'description' => 'Sterling silver pendant necklace'],
                ['name' => 'Watch', 'price' => 149.99, 'stock' => 40, 'description' => 'Classic leather strap watch'],
                ['name' => 'Earrings Set', 'price' => 49.99, 'stock' => 60, 'description' => 'Set of 6 pairs of fashion earrings'],
                ['name' => 'Bracelet', 'price' => 59.99, 'stock' => 55, 'description' => 'Adjustable charm bracelet'],
            ],
            'automotive' => [
                ['name' => 'Car Phone Mount', 'price' => 24.99, 'stock' => 100, 'description' => 'Universal magnetic car phone mount'],
                ['name' => 'Car Air Freshener', 'price' => 9.99, 'stock' => 200, 'description' => 'Long-lasting car air freshener'],
                ['name' => 'Tire Pressure Gauge', 'price' => 14.99, 'stock' => 120, 'description' => 'Digital tire pressure gauge'],
                ['name' => 'Car Cleaning Kit', 'price' => 39.99, 'stock' => 75, 'description' => 'Complete car cleaning kit with tools'],
            ],
        ];

        // Create products for each category
        foreach ($categories as $category) {
            if (isset($productsByCategory[$category->slug])) {
                foreach ($productsByCategory[$category->slug] as $productData) {
                    $vendor = $createdVendors[array_rand($createdVendors)];

                    Product::create([
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'slug' => Str::slug($productData['name']),
                        'description' => $productData['description'],
                        'price' => $productData['price'],
                        'stock' => $productData['stock'],
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
