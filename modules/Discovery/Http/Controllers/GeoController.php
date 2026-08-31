<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Discovery\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ModulesShoppingComplex\Discovery\Services\GeoLocationService;

class GeoController extends Controller
{
    public function __construct(
        private readonly GeoLocationService $geo,
    ) {}

    public function autocomplete(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'suggestions' => $this->geo->autocomplete($query, $this->sessionToken($request)),
        ]);
    }

    public function place(Request $request): JsonResponse
    {
        $placeId = (string) $request->query('place_id', '');

        $details = $this->geo->placeDetails($placeId, $this->sessionToken($request));

        if ($details === null) {
            return response()->json(['error' => 'Place not found.'], 404);
        }

        return response()->json($details);
    }

    public function reverse(Request $request): JsonResponse
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return response()->json(['error' => 'Invalid coordinates.'], 422);
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return response()->json(['error' => 'Invalid coordinates.'], 422);
        }

        return response()->json(['label' => $this->geo->reverseGeocode($lat, $lng)]);
    }

    private function sessionToken(Request $request): ?string
    {
        $token = mb_substr((string) $request->query('session', ''), 0, 64);

        return $token !== '' ? $token : null;
    }
}
