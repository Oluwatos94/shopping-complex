<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google_maps.key', 'test-key');
        Http::preventStrayRequests();
    }

    public function test_a_guest_can_search_for_a_place(): void
    {
        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'suggestions' => [
                    ['placePrediction' => ['placeId' => 'abc123', 'text' => ['text' => 'Yaba, Lagos']]],
                ],
            ]),
        ]);

        $this->getJson('/api/geo/autocomplete?q=yaba')
            ->assertOk()
            ->assertJsonPath('suggestions.0.place_id', 'abc123')
            ->assertJsonPath('suggestions.0.description', 'Yaba, Lagos');
    }

    public function test_a_guest_can_name_a_dropped_pin(): void
    {
        Http::fake([
            'maps.googleapis.com/maps/api/geocode/*' => Http::response([
                'results' => [[
                    'address_components' => [
                        ['long_name' => 'Ikeja', 'types' => ['locality']],
                        ['long_name' => 'Lagos', 'types' => ['administrative_area_level_1']],
                    ],
                ]],
            ]),
        ]);

        $this->getJson('/api/geo/reverse?lat=6.6018&lng=3.3515')
            ->assertOk()
            ->assertJsonPath('label', 'Ikeja, Lagos');
    }

    public function test_reverse_lookup_rejects_coordinates_out_of_range(): void
    {
        Http::fake();

        $this->getJson('/api/geo/reverse?lat=91&lng=3.3515')->assertStatus(422);
        $this->getJson('/api/geo/reverse?lat=abc&lng=3.3515')->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_autocomplete_is_biased_to_the_configured_regions(): void
    {
        config()->set('services.google_maps.region_codes', ['ng', 'gh']);

        Http::fake(['places.googleapis.com/*' => Http::response(['suggestions' => []])]);

        $this->getJson('/api/geo/autocomplete?q=accra')->assertOk();

        Http::assertSent(fn ($request) => $request['includedRegionCodes'] === ['ng', 'gh']);
    }
}
