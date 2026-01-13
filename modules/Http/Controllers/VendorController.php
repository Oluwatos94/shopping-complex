<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\VendorRequest;
use ModulesShoppingComplex\Services\VendorService;

class VendorController extends Controller
{
    public function __construct(
        private readonly VendorService $vendorService
    ) {}

    public function index(VendorRequest $request): Response
    {
        $filters = $request->getFilters();
        $vendors = $this->vendorService->getNearbyVendors($filters, perPage: 12);

        // Transform vendor data for frontend to match TypeScript interfaces
        $transformedVendors = $vendors->through(function ($vendor) {
            $profileImage = $vendor->media->first()->file_path ?? null;

            return [
                // BaseUser fields
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'email_verified_at' => $vendor->email_verified_at?->toISOString(),
                'created_at' => $vendor->created_at->toISOString(),
                'updated_at' => $vendor->updated_at->toISOString(),

                // Vendor-specific fields
                'role' => 'vendor',
                'business_name' => $vendor->business_name ?? $vendor->name,
                'business_description' => $vendor->bio,
                'business_logo' => $profileImage,
                'rating' => 4.5, // Placeholder - will come from reviews table
                'total_sales' => 0, // Placeholder - not tracking sales yet
                'products_count' => $vendor->products_count ?? $vendor->products->count(),
                'is_verified' => $vendor->email_verified_at !== null,
                'is_online' => true, // Placeholder - will be real-time WebSocket status

                // NearbyVendor fields
                'distance_km' => round($vendor->distance_km ?? 0, 2),
                'distance_formatted' => $this->formatDistance($vendor->distance_km ?? 0),
                'response_time_minutes' => 15, // Placeholder
                'avg_response_time' => 15, // Placeholder
                'reviews_count' => 0, // Placeholder

                // Optional location (when GPS fields are added)
                'location' => null, // Will be populated when GPS fields exist
            ];
        });

        return Inertia::render('Vendors/Index', [
            'vendors' => $transformedVendors,
            'filters' => $filters,
            'auth' => [
                'user' => $request->user(),
            ],
        ]);
    }

    /**
     * Format distance for display
     */
    private function formatDistance(float $distanceKm): string
    {
        if ($distanceKm < 1) {
            return round($distanceKm * 1000).' m';
        }

        return round($distanceKm, 1).' km';
    }
}
