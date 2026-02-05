<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use ModulesShoppingComplex\Http\Requests\SaveOnboardingRequest;
use ModulesShoppingComplex\Http\Requests\SubmitOnboardingRequest;
use ModulesShoppingComplex\Http\Requests\VendorRequest;
use ModulesShoppingComplex\Models\Category;
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
            $profileImage = $vendor->media->first()?->file_path;

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

    /**
     * Show vendor onboarding form.
     */
    public function onboarding(): Response|RedirectResponse
    {
        $user = Auth::user();

        // Only vendors can access onboarding (verification)
        if ($user->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can access the verification process.');
        }

        $onboarding = $this->vendorService->getOnboarding($user->id);

        // If already verified, redirect to products page
        if ($onboarding?->isApproved()) {
            return redirect()->route('products.index')
                ->with('info', 'Your vendor account is already verified.');
        }

        // If pending review, show status page
        if ($onboarding?->isPendingReview()) {
            return redirect()->route('vendor.onboarding.success');
        }

        // Cache categories since they rarely change
        $categories = Cache::remember('vendor_onboarding_categories', 3600, fn () => Category::select('id', 'name', 'slug')->orderBy('name')->get()
        );

        $savedData = null;
        if ($onboarding) {
            $savedData = [
                'business_info' => [
                    'legal_entity_name' => $onboarding->legal_entity_name ?? '',
                    'business_category' => $onboarding->business_category ?? '',
                    'tax_identification_number' => $onboarding->tax_identification_number ?? '',
                    'physical_address' => $onboarding->physical_address ?? '',
                ],
                'verification' => [
                    'certificate_of_incorporation' => null,
                    'government_issued_id' => null,
                    'proof_of_address' => null,
                    'certificate_of_incorporation_preview' => $onboarding->certificate_of_incorporation
                        ? Storage::url($onboarding->certificate_of_incorporation)
                        : null,
                    'government_issued_id_preview' => $onboarding->government_issued_id
                        ? Storage::url($onboarding->government_issued_id)
                        : null,
                    'proof_of_address_preview' => $onboarding->proof_of_address
                        ? Storage::url($onboarding->proof_of_address)
                        : null,
                ],
                'bank_details' => [
                    'bank_name' => $onboarding->bank_name ?? '',
                    'bank_branch' => $onboarding->bank_branch ?? '',
                    'account_number' => $onboarding->account_number ?? '',
                    'swift_bic_code' => $onboarding->swift_bic_code ?? '',
                ],
                'agreed_to_terms' => $onboarding->agreed_to_terms,
                'current_step' => $onboarding->current_step,
            ];
        }

        return Inertia::render('Vendor/Onboarding', [
            'categories' => $categories,
            'savedData' => $savedData,
        ]);
    }

    /**
     * Save onboarding progress (draft).
     */
    public function saveOnboarding(SaveOnboardingRequest $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $this->vendorService->saveOnboardingDraft(
            $user,
            $request->getBusinessInfo(),
            $request->getBankDetails(),
            (int) $request->input('current_step', 1),
            $request->getUploadedFiles()
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Progress saved']);
        }

        return redirect()->route('home')->with('success', 'Your progress has been saved.');
    }

    /**
     * Submit onboarding for review.
     */
    public function submitOnboarding(SubmitOnboardingRequest $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        try {
            $this->vendorService->submitOnboarding(
                $user,
                $request->getBusinessInfo(),
                $request->getBankDetails(),
                $request->boolean('agreed_to_terms'),
                $request->getUploadedFiles()
            );
        } catch (InvalidArgumentException $e) {
            $errors = json_decode($e->getMessage(), true);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $errors], 422);
            }

            return back()->withErrors($errors);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Application submitted successfully']);
        }

        return redirect()->route('vendor.onboarding.success')
            ->with('success', 'Your vendor application has been submitted for review.');
    }

    /**
     * Show onboarding success page.
     */
    public function onboardingSuccess(): Response
    {
        return Inertia::render('Vendor/OnboardingSuccess');
    }
}
