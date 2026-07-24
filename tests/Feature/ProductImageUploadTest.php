<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $vendor;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create a vendor user
        $this->vendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        // Create a product owned by this vendor
        $this->product = Product::factory()->create([
            'vendor_id' => $this->vendor->id,
        ]);
    }

    public function test_vendor_can_upload_single_image(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 1000, 1000)->size(2048); // 2MB

        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Images uploaded successfully.',
        ]);

        $this->assertDatabaseHas('media', [
            'model_type' => Product::class,
            'model_id' => $this->product->id,
            'type' => 'product_image',
        ]);

        // Check file was stored
        $media = $this->product->media()->first();
        Storage::disk('public')->assertExists($media->url);
    }

    public function test_vendor_can_upload_multiple_images(): void
    {
        $files = [
            UploadedFile::fake()->image('product1.jpg', 1000, 1000)->size(2048),
            UploadedFile::fake()->image('product2.png', 1000, 1000)->size(2048),
            UploadedFile::fake()->image('product3.webp', 1000, 1000)->size(2048),
        ];

        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => $files,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Images uploaded successfully.',
        ]);

        $this->assertCount(3, $this->product->media);

        // Check all files were stored
        foreach ($this->product->media as $media) {
            Storage::disk('public')->assertExists($media->url);
        }
    }

    public function test_image_upload_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024);

        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('images.0');
    }

    public function test_image_upload_validates_file_size(): void
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(6144); // 6MB (exceeds 5MB limit)

        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('images.0');
    }

    public function test_image_upload_validates_dimensions(): void
    {
        $file = UploadedFile::fake()->image('small.jpg', 50, 50); // Too small (min 100x100)

        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('images.0');
    }

    public function test_image_upload_validates_max_images(): void
    {
        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->image("product{$i}.jpg", 1000, 1000)->size(1024);
        }

        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => $files,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('images');
    }

    public function test_image_upload_requires_at_least_one_image(): void
    {
        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('images');
    }

    public function test_vendor_can_delete_product_image(): void
    {
        // Upload an image first
        $file = UploadedFile::fake()->image('product.jpg', 1000, 1000)->size(2048);

        $uploadResponse = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $mediaId = $uploadResponse->json('media.0.id');

        // Delete the image
        $response = $this->actingAs($this->vendor)
            ->deleteJson("/products/{$this->product->id}/images/{$mediaId}");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Image deleted successfully.',
        ]);

        $this->assertDatabaseMissing('media', [
            'id' => $mediaId,
        ]);
    }

    public function test_vendor_can_get_all_product_images(): void
    {
        // Upload multiple images
        $files = [
            UploadedFile::fake()->image('product1.jpg', 1000, 1000)->size(2048),
            UploadedFile::fake()->image('product2.jpg', 1000, 1000)->size(2048),
        ];

        $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => $files,
            ]);

        // Get all images
        $response = $this->actingAs($this->vendor)
            ->getJson("/products/{$this->product->id}/images");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertCount(2, $response->json('media'));
    }

    public function test_non_vendor_cannot_upload_images(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('product.jpg', 1000, 1000)->size(2048);

        $response = $this->actingAs($customer)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $response->assertStatus(403); // Forbidden
    }

    public function test_vendor_cannot_upload_images_to_other_vendor_product(): void
    {
        $otherVendor = User::factory()->create([
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('product.jpg', 1000, 1000)->size(2048);

        $response = $this->actingAs($otherVendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $response->assertStatus(403); // Forbidden
    }

    public function test_guest_cannot_upload_images(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 1000, 1000)->size(2048);

        $response = $this->postJson("/products/{$this->product->id}/images", [
            'images' => [$file],
        ]);

        $response->assertStatus(401); // Unauthorized
    }

    public function test_accepted_image_formats(): void
    {
        $formats = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($formats as $format) {
            $file = UploadedFile::fake()->image("product.{$format}", 1000, 1000)->size(2048);

            $response = $this->actingAs($this->vendor)
                ->postJson("/products/{$this->product->id}/images", [
                    'images' => [$file],
                ]);

            $response->assertStatus(200);
        }
    }

    public function test_image_is_optimized_before_storage(): void
    {
        // Create a large image (5000x5000 - exceeds max 1920x1920)
        $file = UploadedFile::fake()->image('large-product.jpg', 5000, 5000)->size(4096);

        $response = $this->actingAs($this->vendor)
            ->postJson("/products/{$this->product->id}/images", [
                'images' => [$file],
            ]);

        $response->assertStatus(200);

        $media = $this->product->media()->first();
        Storage::disk('public')->assertExists($media->url);

        // Get the stored file size
        $storedSize = Storage::disk('public')->size($media->url);
        $originalSize = $file->getSize();

        // Optimized file should be smaller than original
        $this->assertLessThan($originalSize, $storedSize);
    }
}
