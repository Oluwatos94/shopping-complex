<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use ModulesShoppingComplex\Catalog\Models\Product;
use ModulesShoppingComplex\Discovery\Http\Requests\VendorRequest;
use ModulesShoppingComplex\Identity\Models\Address;
use ModulesShoppingComplex\Identity\Models\User;
use Tests\TestCase;

class VendorProximityTest extends TestCase
{
    use RefreshDatabase;

    private const LAT = 6.5244;

    private const LNG = 3.3792;

    /** One degree of latitude is ~111km, so this places a vendor a known distance due north. */
    private function vendorKmNorth(string $businessName, float $km): User
    {
        $vendor = User::factory()->create(['role' => 'vendor', 'business_name' => $businessName]);

        Address::create([
            'user_id' => $vendor->id,
            'street' => '1 Test Road',
            'city' => 'Lagos',
            'state' => 'Lagos',
            'country' => 'Nigeria',
            'latitude' => self::LAT + ($km / 111.0),
            'longitude' => self::LNG,
        ]);

        return $vendor;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<int, string>
     */
    private function listedVendors(array $params): array
    {
        $names = [];

        $this->get('/vendors?'.http_build_query($params))
            ->assertOk()
            ->assertInertia(function (AssertableInertia $page) use (&$names) {
                $page->component('Vendors/Index', false);
                /** @var array<int, array<string, mixed>> $rows */
                $rows = $page->toArray()['props']['vendors']['data'];
                $names = array_column($rows, 'business_name');
            });

        return $names;
    }

    /**
     * @return array<string, mixed>
     */
    private function nearMe(int $radius): array
    {
        return [
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'sort_by' => 'distance',
            'radius' => $radius,
        ];
    }

    public function test_widening_the_radius_surfaces_vendors_the_narrower_search_missed(): void
    {
        $this->vendorKmNorth('Corner Shop', 3);
        $this->vendorKmNorth('Across Town', 15);
        $this->vendorKmNorth('Next City', 25);

        $this->assertSame(['Corner Shop'], $this->listedVendors($this->nearMe(5)));
        $this->assertSame(['Corner Shop', 'Across Town'], $this->listedVendors($this->nearMe(20)));
        $this->assertSame(
            ['Corner Shop', 'Across Town', 'Next City'],
            $this->listedVendors($this->nearMe(VendorRequest::MAX_RADIUS_KM))
        );
    }

    public function test_every_radius_offered_by_the_listing_page_is_accepted(): void
    {
        $this->vendorKmNorth('Corner Shop', 1);

        // Mirrors radiusOptions in resources/ts/pages/Vendors/Index.tsx.
        foreach ([5, 10, 20, 30] as $radius) {
            $this->get('/vendors?'.http_build_query($this->nearMe($radius)))
                ->assertOk()
                ->assertSessionHasNoErrors();
        }
    }

    public function test_a_radius_beyond_the_supported_maximum_is_rejected(): void
    {
        $this->get('/vendors?'.http_build_query($this->nearMe(VendorRequest::MAX_RADIUS_KM + 1)))
            ->assertSessionHasErrors('radius');
    }

    public function test_sorting_by_product_count_is_accepted_and_applied(): void
    {
        $quiet = $this->vendorKmNorth('Quiet Shop', 2);
        $busy = $this->vendorKmNorth('Busy Shop', 4);

        Product::factory()->count(3)
            ->create(['vendor_id' => $busy->id, 'is_active' => true]);
        Product::factory()
            ->create(['vendor_id' => $quiet->id, 'is_active' => true]);

        $listed = $this->listedVendors([
            'latitude' => self::LAT,
            'longitude' => self::LNG,
            'radius' => 20,
            'sort_by' => 'products_count',
        ]);

        $this->assertSame(['Busy Shop', 'Quiet Shop'], $listed);
    }

    public function test_vendors_without_coordinates_are_absent_from_a_proximity_search(): void
    {
        $this->vendorKmNorth('Mapped Vendor', 2);
        User::factory()->create(['role' => 'vendor', 'business_name' => 'Unmapped Vendor']);

        $this->assertSame(['Mapped Vendor'], $this->listedVendors($this->nearMe(VendorRequest::MAX_RADIUS_KM)));

        // They are only hidden by the distance filter, not missing from the listing.
        $this->assertContains('Unmapped Vendor', $this->listedVendors(['sort_by' => 'newest']));
    }
}
