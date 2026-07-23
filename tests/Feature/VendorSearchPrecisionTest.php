<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Services\VendorService;
use ModulesShoppingComplex\WhatsApp\Models\WhatsAppSession;
use ModulesShoppingComplex\WhatsApp\Repositories\WhatsAppSessionRepository;
use Tests\TestCase;

class VendorSearchPrecisionTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $businessName, string $categoryName, array $product): User
    {
        $category = Category::factory()->create(['name' => $categoryName]);
        $vendor = User::factory()->create([
            'role' => 'vendor',
            'business_name' => $businessName,
            'category_id' => $category->id,
        ]);

        Product::factory()->create(array_merge([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'is_active' => true,
        ], $product));

        return $vendor;
    }

    public function test_multi_word_query_requires_all_terms(): void
    {
        $designer = $this->makeVendor('Bespoke Styles', 'Fashion and Clothing', [
            'name' => 'Custom gown',
            'description' => 'Made to measure',
            'tags' => ['fashion designer'],
        ]);
        $this->makeVendor('Estyjoks collection', 'Fashion and Clothing', [
            'name' => 'Pants',
            'description' => 'Quality pants',
            'tags' => null,
        ]);

        $service = $this->app->make(VendorService::class);

        $strict = $service->findByQuery('fashion designer');
        $this->assertSame([$designer->id], $strict->pluck('id')->all());

        $loose = $service->findByQuery('fashion designer', loose: true);
        $this->assertSame([$designer->id], $loose->pluck('id')->all());
    }

    public function test_strict_search_ignores_description_and_category(): void
    {
        $printer = $this->makeVendor('Allizbee Printing', 'Printing', [
            'name' => 'Roll-up Banner',
            'description' => 'Banners, book publishing, clothes customized',
            'tags' => null,
        ]);
        $clothier = $this->makeVendor('Lumina Wears', 'Fashion and Clothing', [
            'name' => 'Knitted 2 piece clothes',
            'description' => 'Fine knitwear',
            'tags' => null,
        ]);

        $service = $this->app->make(VendorService::class);

        $strict = $service->findByQuery('clothes');
        $this->assertSame([$clothier->id], $strict->pluck('id')->all());

        $loose = $service->findByQuery('clothes', loose: true);
        $this->assertEqualsCanonicalizing([$printer->id, $clothier->id], $loose->pluck('id')->all());
    }

    public function test_loose_search_matches_category_when_strict_finds_nothing(): void
    {
        $vendor = $this->makeVendor('Estyjoks collection', 'Fashion and Clothing', [
            'name' => 'Pants',
            'description' => 'Quality pants',
            'tags' => null,
        ]);

        $service = $this->app->make(VendorService::class);

        $this->assertTrue($service->findByQuery('fashion')->isEmpty());
        $this->assertSame([$vendor->id], $service->findByQuery('fashion', loose: true)->pluck('id')->all());
    }

    public function test_find_or_create_does_not_bump_last_active_at(): void
    {
        WhatsAppSession::create([
            'phone_number' => '2348000000001',
            'state' => 'idle',
            'data' => ['history' => [['role' => 'user', 'content' => 'old']]],
            'last_active_at' => now()->subHours(2),
        ]);

        $repository = $this->app->make(WhatsAppSessionRepository::class);
        $session = $repository->findOrCreate('2348000000001');

        $this->assertGreaterThanOrEqual(60, $session->last_active_at->diffInMinutes(now()));

        $repository->touch($session);
        $this->assertLessThan(1, $session->fresh()->last_active_at->diffInMinutes(now()));
    }
}
