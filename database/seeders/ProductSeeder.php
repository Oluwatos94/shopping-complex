<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\Conversation;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\Media;
use ModulesShoppingComplex\Models\Review;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create customer users
        $customers = [];
        $customerData = [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['name' => 'Mike Johnson', 'email' => 'mike@example.com'],
            ['name' => 'Sarah Wilson', 'email' => 'sarah@example.com'],
            ['name' => 'David Brown', 'email' => 'david@example.com'],
            ['name' => 'Emily Davis', 'email' => 'emily@example.com'],
            ['name' => 'Chris Lee', 'email' => 'chris@example.com'],
            ['name' => 'Amanda White', 'email' => 'amanda@example.com'],
        ];

        foreach ($customerData as $data) {
            $customers[] = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => 'customer',
                    'email_verified_at' => now(),
                ]
            );
        }

        // Create vendor users with profile images
        $vendors = [];
        $vendorData = [
            [
                'name' => 'Fashion Hub',
                'email' => 'fashion@example.com',
                'business_name' => 'Fashion Hub',
                'bio' => 'Your one-stop shop for trendy clothing and accessories. We offer the latest fashion trends at affordable prices.',
            ],
            [
                'name' => 'Tech Store',
                'email' => 'tech@example.com',
                'business_name' => 'Tech Store',
                'bio' => 'Latest electronics and gadgets at competitive prices. Quality guaranteed with excellent customer service.',
            ],
            [
                'name' => 'Home Decor Plus',
                'email' => 'homedecor@example.com',
                'business_name' => 'Home Decor Plus',
                'bio' => 'Beautiful home furnishings and garden supplies to transform your living space into a paradise.',
            ],
            [
                'name' => 'Beauty Haven',
                'email' => 'beauty@example.com',
                'business_name' => 'Beauty Haven',
                'bio' => 'Premium beauty and personal care products from top brands. Look your best every day.',
            ],
            [
                'name' => 'Sports Central',
                'email' => 'sports@example.com',
                'business_name' => 'Sports Central',
                'bio' => 'All your sporting needs in one place. From fitness equipment to outdoor gear.',
            ],
        ];

        foreach ($vendorData as $data) {
            $vendor = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'slug' => Str::slug($data['business_name']).'-'.uniqid(),
                    'password' => Hash::make('password'),
                    'role' => 'vendor',
                    'business_name' => $data['business_name'],
                    'bio' => $data['bio'],
                    'email_verified_at' => now(),
                ]
            );

            // Add vendor logo only if not exists
            if ($vendor->media()->where('type', 'avatar')->doesntExist()) {
                Media::create([
                    'url' => 'https://ui-avatars.com/api/?name='.urlencode($data['business_name']).'&background=86885e&color=fff&size=200',
                    'type' => 'avatar',
                    'model_id' => $vendor->id,
                    'model_type' => User::class,
                ]);
            }

            // Ensure vendor has an approved onboarding record
            VendorOnboarding::firstOrCreate(
                ['user_id' => $vendor->id],
                [
                    'legal_entity_name' => $data['business_name'],
                    'status' => VendorOnboardingStatusEnum::APPROVED,
                    'current_step' => 4,
                    'agreed_to_terms' => true,
                    'reviewed_at' => now(),
                ]
            );

            $vendors[] = $vendor;
        }

        // Get all categories
        $categories = Category::all()->keyBy('slug');

        // Sample products with images — keys must match slugs in CategorySeeder
        $productsByCategory = [
            'fashion-clothing' => [
                ['name' => 'Classic Cotton T-Shirt', 'price' => 19.99, 'sale_price' => null, 'stock' => 100, 'is_featured' => true, 'description' => 'Comfortable 100% cotton t-shirt available in multiple colors. Perfect for everyday casual wear with breathable fabric that keeps you cool.', 'image_id' => 1],
                ['name' => 'Denim Jeans', 'price' => 59.99, 'sale_price' => 49.99, 'stock' => 75, 'is_featured' => false, 'description' => 'Stylish slim-fit denim jeans with modern cut. Made from premium denim with just the right amount of stretch for comfort.', 'image_id' => 2],
                ['name' => 'Summer Floral Dress', 'price' => 45.99, 'sale_price' => 39.99, 'stock' => 50, 'is_featured' => true, 'description' => 'Lightweight floral summer dress perfect for warm weather. Features a flattering A-line silhouette and comfortable fit.', 'image_id' => 3],
                ['name' => 'Leather Jacket', 'price' => 149.99, 'sale_price' => 129.99, 'stock' => 30, 'is_featured' => true, 'description' => 'Genuine leather jacket with classic design. Timeless style that never goes out of fashion. Lined for extra comfort.', 'image_id' => 4],
                ['name' => 'Running Shoes Pro', 'price' => 89.99, 'sale_price' => 79.99, 'stock' => 8, 'is_featured' => false, 'description' => 'Lightweight running shoes with excellent cushioning and arch support. Breathable mesh upper keeps feet cool during workouts.', 'image_id' => 5],
                ['name' => 'Winter Parka Coat', 'price' => 179.99, 'sale_price' => 149.99, 'stock' => 40, 'is_featured' => false, 'description' => 'Warm winter coat with water-resistant fabric and faux fur hood. Insulated for extreme cold weather protection.', 'image_id' => 6],
                ['name' => 'Casual Sneakers', 'price' => 69.99, 'sale_price' => 59.99, 'stock' => 0, 'is_featured' => false, 'description' => 'Comfortable casual sneakers for everyday wear. Versatile design that pairs well with jeans or shorts.', 'image_id' => 7],
                ['name' => 'Premium Polo Shirt', 'price' => 39.99, 'sale_price' => null, 'stock' => 90, 'is_featured' => false, 'description' => 'Classic polo shirt made from premium cotton pique. Features a comfortable regular fit and ribbed collar.', 'image_id' => 8],
            ],
            'electronics-repairs' => [
                ['name' => 'Wireless Noise-Canceling Earbuds', 'price' => 129.99, 'sale_price' => 89.99, 'stock' => 120, 'is_featured' => true, 'description' => 'Premium wireless earbuds with active noise cancellation. 30-hour battery life with charging case. Crystal clear audio quality.', 'image_id' => 20],
                ['name' => 'Premium Smartphone Case', 'price' => 29.99, 'sale_price' => 24.99, 'stock' => 200, 'is_featured' => false, 'description' => 'Durable protective case for smartphones with military-grade drop protection. Slim design with raised edges for screen protection.', 'image_id' => 21],
                ['name' => '20000mAh Power Bank', 'price' => 49.99, 'sale_price' => 39.99, 'stock' => 150, 'is_featured' => true, 'description' => 'High-capacity portable power bank with fast charging. Charges 3 devices simultaneously with smart charging technology.', 'image_id' => 22],
                ['name' => 'Waterproof Bluetooth Speaker', 'price' => 79.99, 'sale_price' => 59.99, 'stock' => 85, 'is_featured' => false, 'description' => 'IPX7 waterproof Bluetooth speaker with 24-hour battery life. 360-degree immersive sound with deep bass.', 'image_id' => 23],
                ['name' => 'Ergonomic Laptop Stand', 'price' => 44.99, 'sale_price' => 34.99, 'stock' => 5, 'is_featured' => false, 'description' => 'Adjustable aluminum laptop stand with 6 height levels. Improves posture and increases airflow for cooling.', 'image_id' => 24],
                ['name' => 'USB-C Fast Charging Cable', 'price' => 19.99, 'sale_price' => 14.99, 'stock' => 300, 'is_featured' => false, 'description' => '100W USB-C to USB-C fast charging cable (6ft). Braided nylon construction for durability. Compatible with all USB-C devices.', 'image_id' => 25],
            ],
            'accessories-lifestyle' => [
                ['name' => 'Decorative Throw Pillow Set', 'price' => 39.99, 'sale_price' => 29.99, 'stock' => 75, 'is_featured' => true, 'description' => 'Set of 4 premium decorative throw pillows with removable covers. Modern geometric patterns in neutral colors.', 'image_id' => 40],
                ['name' => 'Abstract Canvas Wall Art', 'price' => 59.99, 'sale_price' => 44.99, 'stock' => 50, 'is_featured' => false, 'description' => 'Modern abstract canvas wall art with gallery-wrapped frame. Ready to hang. Dimensions: 24x36 inches.', 'image_id' => 41],
                ['name' => '10-Piece Garden Tool Set', 'price' => 64.99, 'sale_price' => 54.99, 'stock' => 40, 'is_featured' => false, 'description' => 'Complete garden tool set with ergonomic handles. Includes trowel, cultivator, pruner, and more. Comes with carrying bag.', 'image_id' => 42],
                ['name' => 'Modern LED Desk Lamp', 'price' => 49.99, 'sale_price' => 39.99, 'stock' => 60, 'is_featured' => true, 'description' => 'Touch-control LED desk lamp with 5 brightness levels and 3 color temperatures. USB charging port. Energy efficient.', 'image_id' => 43],
                ['name' => 'Professional Chef Knife Set', 'price' => 99.99, 'sale_price' => 79.99, 'stock' => 45, 'is_featured' => false, 'description' => '8-piece professional kitchen knife set with wooden block. High-carbon stainless steel blades. Ergonomic handles.', 'image_id' => 44],
            ],
            'health-beauty' => [
                ['name' => 'Hydrating Face Moisturizer', 'price' => 44.99, 'sale_price' => 34.99, 'stock' => 100, 'is_featured' => true, 'description' => 'Lightweight hydrating moisturizer for all skin types. Contains hyaluronic acid and vitamin E. Dermatologist tested.', 'image_id' => 60],
                ['name' => 'Professional Makeup Brush Set', 'price' => 59.99, 'sale_price' => 49.99, 'stock' => 75, 'is_featured' => false, 'description' => '15-piece professional makeup brush set with vegan synthetic bristles. Includes travel case. Perfect for beginners and pros.', 'image_id' => 61],
                ['name' => '3-in-1 Hair Styling Tool', 'price' => 99.99, 'sale_price' => 89.99, 'stock' => 50, 'is_featured' => true, 'description' => 'Versatile hair styling tool: straightener, curler, and waver. Ceramic plates with adjustable temperature. Auto shut-off.', 'image_id' => 62],
                ['name' => 'Luxury Eau de Parfum', 'price' => 89.99, 'sale_price' => 69.99, 'stock' => 60, 'is_featured' => false, 'description' => 'Elegant floral fragrance with notes of jasmine, rose, and sandalwood. Long-lasting formula. 50ml bottle.', 'image_id' => 63],
                ['name' => 'Complete Skincare Set', 'price' => 119.99, 'sale_price' => 99.99, 'stock' => 40, 'is_featured' => false, 'description' => '5-step skincare routine: cleanser, toner, serum, moisturizer, and eye cream. Suitable for all skin types.', 'image_id' => 64],
            ],
            'services' => [
                ['name' => 'Premium Yoga Mat', 'price' => 39.99, 'sale_price' => 29.99, 'stock' => 100, 'is_featured' => true, 'description' => 'Extra thick non-slip yoga mat with carrying strap. Eco-friendly TPE material. 6mm cushioning for joint protection.', 'image_id' => 80],
                ['name' => 'Adjustable Dumbbell Set', 'price' => 99.99, 'sale_price' => 79.99, 'stock' => 50, 'is_featured' => true, 'description' => 'Space-saving adjustable dumbbell set (5-25 lbs each). Quick weight change system. Perfect for home workouts.', 'image_id' => 81],
                ['name' => 'Insulated Water Bottle', 'price' => 29.99, 'sale_price' => 19.99, 'stock' => 150, 'is_featured' => false, 'description' => 'Double-wall vacuum insulated stainless steel bottle (32oz). Keeps drinks cold 24hrs or hot 12hrs. BPA-free.', 'image_id' => 82],
                ['name' => 'Resistance Band Set', 'price' => 34.99, 'sale_price' => 24.99, 'stock' => 120, 'is_featured' => false, 'description' => '5 resistance bands with handles, door anchor, and ankle straps. Various resistance levels for full-body workouts.', 'image_id' => 83],
                ['name' => '4-Person Camping Tent', 'price' => 169.99, 'sale_price' => 149.99, 'stock' => 3, 'is_featured' => false, 'description' => 'Waterproof 4-person dome tent with rainfly. Easy setup in minutes. Includes carrying bag and stakes.', 'image_id' => 84],
            ],
            'books-education' => [
                ['name' => 'Premium Notebook Set', 'price' => 19.99, 'sale_price' => 14.99, 'stock' => 200, 'is_featured' => false, 'description' => 'Pack of 3 premium hardcover notebooks with 120gsm paper. Lay-flat binding. Perfect for journaling and note-taking.', 'image_id' => 100],
                ['name' => 'Colorful Gel Pen Collection', 'price' => 24.99, 'sale_price' => 19.99, 'stock' => 150, 'is_featured' => false, 'description' => 'Set of 24 vibrant gel pens with smooth ink flow. Includes neon, pastel, and metallic colors. Quick-drying.', 'image_id' => 101],
                ['name' => 'Bamboo Desk Organizer', 'price' => 39.99, 'sale_price' => 29.99, 'stock' => 80, 'is_featured' => true, 'description' => 'Natural bamboo desk organizer with 8 compartments. Holds pens, phone, business cards, and more. Eco-friendly.', 'image_id' => 102],
                ['name' => 'Complete Art Supplies Kit', 'price' => 79.99, 'sale_price' => 59.99, 'stock' => 60, 'is_featured' => false, 'description' => 'Professional art kit with colored pencils, watercolors, pastels, and sketching supplies. 100+ pieces in wooden case.', 'image_id' => 103],
            ],
            'groceries-food' => [
                ['name' => '500-Piece Building Blocks', 'price' => 49.99, 'sale_price' => 39.99, 'stock' => 100, 'is_featured' => true, 'description' => 'Creative building blocks set with 500 pieces in various shapes and colors. Compatible with major brands. Ages 4+.', 'image_id' => 120],
                ['name' => 'Strategy Board Game', 'price' => 39.99, 'sale_price' => 29.99, 'stock' => 75, 'is_featured' => false, 'description' => 'Award-winning family strategy game for 2-6 players. Easy to learn, challenging to master. Ages 8+. 60-90 min play time.', 'image_id' => 121],
                ['name' => '1000-Piece Puzzle', 'price' => 29.99, 'sale_price' => 24.99, 'stock' => 90, 'is_featured' => false, 'description' => 'Beautiful landscape 1000-piece jigsaw puzzle. Premium quality pieces with anti-glare surface. Completed size: 27x20 inches.', 'image_id' => 122],
                ['name' => 'Collectible Action Figure', 'price' => 24.99, 'sale_price' => 19.99, 'stock' => 120, 'is_featured' => false, 'description' => 'Highly detailed collectible action figure with 20 points of articulation. Includes accessories and display stand.', 'image_id' => 123],
            ],
        ];

        // Create products
        $createdProducts = [];
        foreach ($categories as $slug => $category) {
            if (isset($productsByCategory[$slug])) {
                foreach ($productsByCategory[$slug] as $productData) {
                    $vendor = $vendors[array_rand($vendors)];

                    $product = Product::create([
                        'vendor_id' => $vendor->id,
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'slug' => Str::slug($productData['name']).'-'.Str::random(5),
                        'description' => $productData['description'],
                        'price' => $productData['price'],
                        'stock' => $productData['stock'],
                        'is_active' => true,
                    ]);

                    // Add product images using picsum.photos
                    $imageCount = rand(2, 5);
                    for ($i = 0; $i < $imageCount; $i++) {
                        Media::create([
                            'url' => 'https://picsum.photos/seed/'.$productData['image_id'].$i.'/800/800',
                            'type' => $i === 0 ? 'primary' : 'gallery',
                            'model_id' => $product->id,
                            'model_type' => Product::class,
                        ]);
                    }

                    $createdProducts[] = $product;
                }
            }
        }

        // Create conversations between customers and vendors (required for reviews)
        $conversations = [];
        foreach ($customers as $customer) {
            // Each customer has conversations with 2-3 random vendors
            $customerVendors = array_slice($vendors, 0, rand(2, 3));
            foreach ($customerVendors as $vendor) {
                $conversation = Conversation::firstOrCreate([
                    'customer_id' => $customer->id,
                    'vendor_id' => $vendor->id,
                ]);
                $conversations[] = [
                    'conversation' => $conversation,
                    'customer' => $customer,
                    'vendor' => $vendor,
                ];
            }
        }

        // Create vendor reviews
        $reviewTitles = [
            5 => ['Excellent service!', 'Highly recommended!', 'Best vendor ever!', 'Amazing experience!', 'Outstanding!'],
            4 => ['Great experience', 'Very satisfied', 'Good service', 'Would recommend', 'Nice vendor'],
            3 => ['Decent service', 'Average experience', 'Okay overall', 'Fair enough', 'Could be better'],
            2 => ['Disappointing', 'Below expectations', 'Needs improvement', 'Not satisfied', 'Could improve'],
            1 => ['Poor service', 'Very disappointed', 'Would not recommend', 'Terrible experience', 'Avoid'],
        ];

        $reviewComments = [
            5 => [
                'The vendor was incredibly responsive and helpful. The products arrived faster than expected and the quality exceeded my expectations. Will definitely buy from them again!',
                'Absolutely fantastic service from start to finish. The vendor went above and beyond to ensure I was satisfied with my purchase. Highly recommend!',
                'Best shopping experience I\'ve had in a long time. The vendor answered all my questions promptly and the product was exactly as described.',
            ],
            4 => [
                'Good experience overall. The vendor was helpful and the product quality was good. Shipping was a bit slow but nothing major.',
                'Satisfied with my purchase. The vendor was professional and the item met my expectations. Would shop here again.',
                'Nice vendor to deal with. Communication was good and the product arrived in good condition.',
            ],
            3 => [
                'The service was okay. Nothing special but nothing bad either. Product was as described.',
                'Average experience. The vendor was responsive but shipping took longer than expected.',
                'Decent vendor. Product quality was acceptable but could be better for the price.',
            ],
            2 => [
                'Disappointed with the service. The vendor took too long to respond and shipping was delayed.',
                'Not very satisfied. The product quality was lower than expected based on the description.',
            ],
            1 => [
                'Very poor experience. Would not recommend. Had issues with the product and vendor was not helpful.',
            ],
        ];

        foreach ($conversations as $conv) {
            // Skip if review already exists for this conversation
            if (Review::where('conversation_id', $conv['conversation']->id)->exists()) {
                continue;
            }

            // 80% chance of leaving a review
            if (rand(1, 100) <= 80) {
                $rating = $this->weightedRating();
                $titles = $reviewTitles[$rating];
                $comments = $reviewComments[$rating];

                Review::create([
                    'customer_id' => $conv['customer']->id,
                    'vendor_id' => $conv['vendor']->id,
                    'conversation_id' => $conv['conversation']->id,
                    'rating' => $rating,
                    'title' => $titles[array_rand($titles)],
                    'comment' => $comments[array_rand($comments)],
                    'status' => 'approved',
                    'helpful_count' => rand(0, 50),
                    'not_helpful_count' => rand(0, 10),
                    'vendor_response' => rand(1, 100) <= 30 ? 'Thank you for your feedback! We appreciate your business and look forward to serving you again.' : null,
                    'vendor_responded_at' => rand(1, 100) <= 30 ? now()->subDays(rand(1, 30)) : null,
                    'moderated_at' => now()->subDays(rand(1, 60)),
                ]);
            }
        }

        $this->command->info('Seeded: Admin user (admin@example.com / password)');
        $this->command->info('Seeded: '.count($customers).' customers');
        $this->command->info('Seeded: '.count($vendors).' vendors');
        $this->command->info('Seeded: '.count($createdProducts).' products with images');
        $this->command->info('Seeded: '.count($conversations).' conversations');
        $this->command->info('Seeded: Vendor reviews');
    }

    /**
     * Generate a weighted random rating (biased towards higher ratings).
     */
    private function weightedRating(): int
    {
        $weights = [
            5 => 40,  // 40% chance of 5 stars
            4 => 30,  // 30% chance of 4 stars
            3 => 15,  // 15% chance of 3 stars
            2 => 10,  // 10% chance of 2 stars
            1 => 5,   // 5% chance of 1 star
        ];

        $total = array_sum($weights);
        $random = rand(1, $total);

        $cumulative = 0;
        foreach ($weights as $rating => $weight) {
            $cumulative += $weight;
            if ($random <= $cumulative) {
                return $rating;
            }
        }

        return 5;
    }
}
