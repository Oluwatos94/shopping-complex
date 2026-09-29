<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Identity\Models\User;
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
}
