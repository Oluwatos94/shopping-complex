<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Discovery\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class GeoLocationService
{
    private const AUTOCOMPLETE_URL = 'https://places.googleapis.com/v1/places:autocomplete';

    private const PLACE_DETAILS_URL = 'https://places.googleapis.com/v1/places/';

    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    private const MAX_QUERY_LENGTH = 200;

    private const REVERSE_CACHE_TTL_SECONDS = 86400; // 1 day

    public function __construct(
        private string $apiKey,
    ) {}

    /**
     * Turn raw coordinates into a short, human-readable area label
     * (e.g. "Ikeja, Lagos") using the Geocoding API. Used to confirm
     * back to a WhatsApp buyer the location they just shared.
     *
     * Cached per ~110 m grid cell so repeat shares from the same spot
     * cost nothing.
     */
    public function reverseGeocode(float $lat, float $lng): ?string
    {
        if ($this->apiKey === '') {
            return null;
        }

        $cacheKey = sprintf('geo:rev:%.3f,%.3f', $lat, $lng);
        $cached = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        try {
            $response = Http::timeout(4)->connectTimeout(2)->get(self::GEOCODE_URL, [
                'latlng' => $lat.','.$lng,
                'language' => 'en',
                'key' => $this->apiKey,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Reverse geocode error', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Reverse geocode failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $results = (array) $response->json('results', []);
        if ($results === [] || ! is_array($results[0] ?? null)) {
            return null;
        }

        $components = (array) ($results[0]['address_components'] ?? []);
        $valueKeys = ['long_name', 'short_name'];

        $area = $this->component($components, ['locality', 'sublocality', 'administrative_area_level_2'], $valueKeys);
        $state = $this->component($components, ['administrative_area_level_1'], $valueKeys);

        $label = collect([$area, $state])->filter()->unique()->implode(', ');

        if ($label === '') {
            $label = (string) ($results[0]['formatted_address'] ?? '');
        }

        if ($label === '') {
            return null;
        }

        Cache::put($cacheKey, $label, self::REVERSE_CACHE_TTL_SECONDS);

        return $label;
    }

    /**
     * @return array<int, array{place_id: string, description: string}>
     */
    public function autocomplete(string $query, ?string $sessionToken = null): array
    {
        $query = mb_substr(trim($query), 0, self::MAX_QUERY_LENGTH);
        if ($this->apiKey === '' || mb_strlen($query) < 3) {
            return [];
        }

        $body = [
            'input' => $query,
            'includedRegionCodes' => ['ng'],
            'languageCode' => 'en',
        ];
        if ($sessionToken !== null && $sessionToken !== '') {
            $body['sessionToken'] = $sessionToken;
        }

        try {
            $response = Http::timeout(5)->connectTimeout(3)->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'suggestions.placePrediction.placeId,suggestions.placePrediction.text',
            ])->post(self::AUTOCOMPLETE_URL, $body);
        } catch (\Throwable $e) {
            Log::warning('Places autocomplete error', ['error' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('Places autocomplete failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        return collect((array) $response->json('suggestions', []))
            ->map(function (mixed $suggestion): ?array {
                $prediction = is_array($suggestion) ? ($suggestion['placePrediction'] ?? null) : null;
                if (! is_array($prediction) || empty($prediction['placeId'])) {
                    return null;
                }

                return [
                    'place_id' => (string) $prediction['placeId'],
                    'description' => (string) ($prediction['text']['text'] ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resolve a place ID to its coordinates and normalized address parts.
     *
     * @return array{formatted: string, street: string, city: string, state: string, lat: float, lng: float}|null
     */
    public function placeDetails(string $placeId, ?string $sessionToken = null): ?array
    {
        if ($this->apiKey === '' || $placeId === '') {
            return null;
        }

        $query = [];
        if ($sessionToken !== null && $sessionToken !== '') {
            $query['sessionToken'] = $sessionToken;
        }

        try {
            $response = Http::timeout(5)->connectTimeout(3)->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey,
                'X-Goog-FieldMask' => 'formattedAddress,location,addressComponents',
            ])->get(self::PLACE_DETAILS_URL.rawurlencode($placeId), $query);
        } catch (\Throwable $e) {
            Log::warning('Place details error', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Place details failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $data = (array) $response->json();
        $location = (array) ($data['location'] ?? []);

        if (! isset($location['latitude'], $location['longitude'])) {
            return null;
        }

        $components = (array) ($data['addressComponents'] ?? []);
        $formatted = (string) ($data['formattedAddress'] ?? '');

        $streetNumber = $this->component($components, ['street_number']);
        $route = $this->component($components, ['route']);
        $street = trim($streetNumber.' '.$route);

        if ($street === '') {
            $street = trim(explode(',', $formatted)[0] ?? '') ?: $formatted;
        }

        return [
            'formatted' => $formatted,
            'street' => $street,
            'city' => $this->component($components, ['locality', 'postal_town', 'sublocality', 'administrative_area_level_2']),
            'state' => $this->component($components, ['administrative_area_level_1']),
            'lat' => (float) $location['latitude'],
            'lng' => (float) $location['longitude'],
        ];
    }

    /**
     * @param  array<int, mixed>  $components
     * @param  array<int, string>  $types
     * @param  array<int, string>  $valueKeys
     */
    private function component(array $components, array $types, array $valueKeys = ['longText', 'shortText']): string
    {
        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $componentTypes = (array) ($component['types'] ?? []);
            if (array_intersect($types, $componentTypes) === []) {
                continue;
            }

            foreach ($valueKeys as $key) {
                if (! empty($component[$key])) {
                    return (string) $component[$key];
                }
            }

            return '';
        }

        return '';
    }
}
