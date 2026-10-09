<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\Address;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Reviews\Models\Review;
use Tests\TestCase;

/**
 * Public pages must not hand a vendor's contact details to buyers: the number is
 * what the paid contact flow sells, and email/phone are private account data.
 */
class PublicVendorPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(): User
    {
        return User::factory()->create([
            'role' => 'vendor',
            'business_name' => 'Crystal Wears',
            'whatsapp_number' => '08031234567',
        ]);
    }

    public function test_the_public_vendor_page_does_not_expose_contact_details(): void
    {
        $vendor = $this->vendor();
        Product::factory()->create(['vendor_id' => $vendor->id]);

        $this->get('/vendors/'.$vendor->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('vendor.has_whatsapp', true)
            ->missing('vendor.whatsapp_number')
            ->missing('vendor.email')
            ->missing('products.data.0.vendor.email')
            ->missing('products.data.0.vendor.phone')
            ->missing('products.data.0.vendor.whatsapp_number'));

        $this->actingAs($vendor)->get('/vendors/'.$vendor->slug)->assertInertia(fn (Assert $page) => $page
            ->where('vendor.whatsapp_number', '08031234567'));
    }

    public function test_the_public_product_page_does_not_expose_contact_details(): void
    {
        $vendor = $this->vendor();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $this->get('/products/'.$product->slug)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('vendor.has_whatsapp', true)
            ->missing('vendor.whatsapp_number')
            ->missing('product.vendor'));
    }

    public function test_the_public_product_listing_limits_vendor_fields(): void
    {
        $vendor = $this->vendor();
        Product::factory()->create(['vendor_id' => $vendor->id]);

        $this->get('/products')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.vendor.business_name', 'Crystal Wears')
            ->missing('products.data.0.vendor.email')
            ->missing('products.data.0.vendor.phone')
            ->missing('products.data.0.vendor.whatsapp_number')
            ->missing('products.data.0.vendor.address'));
    }

    public function test_the_public_vendor_listing_does_not_expose_contact_details(): void
    {
        $vendor = $this->vendor();
        Address::create([
            'user_id' => $vendor->id,
            'street' => '1 Test Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'latitude' => 6.5244,
            'longitude' => 3.3792,
        ]);

        $this->get('/vendors?latitude=6.5244&longitude=3.3792')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('vendors.data.0.has_whatsapp', true)
            ->missing('vendors.data.0.email')
            ->missing('vendors.data.0.whatsapp_number'));
    }

    public function test_public_reviews_do_not_expose_reviewer_contact_details(): void
    {
        $vendor = $this->vendor();
        $review = Review::factory()->forVendor($vendor)->approved()->create();

        $this->getJson('/vendors/'.$vendor->slug.'/reviews')->assertOk()
            ->assertJsonPath('reviews.0.customer.id', $review->customer_id)
            ->assertJsonMissingPath('reviews.0.customer.email')
            ->assertJsonMissingPath('reviews.0.customer.phone');

        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->getJson('/reviews/'.$review->id)->assertOk()
            ->assertJsonMissingPath('review.customer.email')
            ->assertJsonMissingPath('review.vendor.email')
            ->assertJsonMissingPath('review.vendor.whatsapp_number');
    }
}
