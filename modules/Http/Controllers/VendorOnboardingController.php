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
use ModulesShoppingComplex\Catalog\Models\Category;
use ModulesShoppingComplex\Http\Requests\SaveOnboardingRequest;
use ModulesShoppingComplex\Http\Requests\SubmitOnboardingRequest;
use ModulesShoppingComplex\Services\VendorService;

class VendorOnboardingController extends Controller
{
    public function __construct(
        private readonly VendorService $vendorService,
    ) {}

    public function onboarding(): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            return redirect()->route('home')
                ->with('error', 'Only vendors can access the verification process.');
        }

        $onboarding = $this->vendorService->getOnboarding($user->id);

        if ($onboarding?->isApproved()) {
            return redirect()->route('products.index')
                ->with('info', 'Your vendor account is already verified.');
        }

        if ($onboarding?->isPendingReview()) {
            return redirect()->route('vendor.onboarding.success');
        }

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
                    'whatsapp_number' => $user->whatsapp_number ?? '',
                ],
                'verification' => [
                    'certificate_of_incorporation' => null,
                    'government_issued_id' => null,
                    'proof_of_address' => null,
                    'certificate_of_incorporation_preview' => $onboarding->certificate_of_incorporation
                        ? $this->getSecureDocumentUrl($onboarding->certificate_of_incorporation)
                        : null,
                    'government_issued_id_preview' => $onboarding->government_issued_id
                        ? $this->getSecureDocumentUrl($onboarding->government_issued_id)
                        : null,
                    'proof_of_address_preview' => $onboarding->proof_of_address
                        ? $this->getSecureDocumentUrl($onboarding->proof_of_address)
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

    public function onboardingSuccess(): Response
    {
        return Inertia::render('Vendor/OnboardingSuccess');
    }

    private function getSecureDocumentUrl(string $path): ?string
    {
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $mimeType = mime_content_type(Storage::disk('local')->path($path)) ?: 'application/octet-stream';
        $content = Storage::disk('local')->get($path);

        return 'data:'.$mimeType.';base64,'.base64_encode($content);
    }
}
