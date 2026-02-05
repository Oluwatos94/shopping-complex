<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;
use ModulesShoppingComplex\Repositories\VendorRepository;

final readonly class VendorService
{
    public function __construct(
        private VendorRepository $vendorRepository
    ) {}

    /**
     * Get nearby vendors with pagination and filtering
     *
     * @param  array<string, mixed>  $filters
     */
    public function getNearbyVendors(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->vendorRepository->findNearby($filters, $perPage);
    }

    /**
     * Get vendor statistics
     *
     * @return array<string, mixed>
     */
    public function getVendorStats(int $vendorId): array
    {
        return $this->vendorRepository->getStats($vendorId);
    }

    /**
     * Get user's onboarding record.
     */
    public function getOnboarding(int $userId): ?VendorOnboarding
    {
        return $this->vendorRepository->findOnboardingByUserId($userId);
    }

    /**
     * Save onboarding progress (draft).
     *
     * @param  array<string, mixed>  $businessInfo
     * @param  array<string, mixed>  $bankDetails
     * @param  array<string, UploadedFile|null>  $files
     */
    public function saveOnboardingDraft(
        User $user,
        array $businessInfo,
        array $bankDetails,
        int $currentStep,
        array $files = []
    ): VendorOnboarding {
        return DB::transaction(function () use ($user, $businessInfo, $bankDetails, $currentStep, $files) {
            $data = $this->buildOnboardingData($businessInfo, $bankDetails);
            $data['current_step'] = $currentStep;
            $data['status'] = VendorOnboardingStatusEnum::DRAFT;

            $onboarding = $this->vendorRepository->updateOrCreateOnboarding($user->id, $data);
            $this->handleFileUploads($onboarding, $files);

            return $onboarding->fresh();
        });
    }

    /**
     * Submit onboarding for review.
     *
     * @param  array<string, mixed>  $businessInfo
     * @param  array<string, mixed>  $bankDetails
     * @param  array<string, UploadedFile|null>  $files
     *
     * @throws \InvalidArgumentException
     */
    public function submitOnboarding(
        User $user,
        array $businessInfo,
        array $bankDetails,
        bool $agreedToTerms,
        array $files = []
    ): VendorOnboarding {
        return DB::transaction(function () use ($user, $businessInfo, $bankDetails, $agreedToTerms, $files) {

            $existingOnboarding = $this->vendorRepository->findOnboardingByUserId($user->id);

            $errors = $this->validateOnboardingSubmission(
                $businessInfo,
                $bankDetails,
                $agreedToTerms,
                $files,
                $existingOnboarding
            );

            if (! empty($errors)) {
                throw new \InvalidArgumentException(json_encode($errors));
            }

            $data = $this->buildOnboardingData($businessInfo, $bankDetails);
            $data['agreed_to_terms'] = $agreedToTerms;
            $data['current_step'] = 4;
            $data['status'] = VendorOnboardingStatusEnum::PENDING_REVIEW;

            $onboarding = $this->vendorRepository->updateOrCreateOnboarding($user->id, $data);
            $this->handleFileUploads($onboarding, $files);

            return $onboarding->fresh();
        });
    }

    /**
     * Approve vendor onboarding (admin).
     */
    public function approveOnboarding(VendorOnboarding $onboarding, User $reviewer): VendorOnboarding
    {
        return $this->vendorRepository->updateOnboarding($onboarding, [
            'status' => VendorOnboardingStatusEnum::APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Reject vendor onboarding (admin).
     */
    public function rejectOnboarding(
        VendorOnboarding $onboarding,
        User $reviewer,
        string $reason
    ): VendorOnboarding {
        return $this->vendorRepository->updateOnboarding($onboarding, [
            'status' => VendorOnboardingStatusEnum::REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get pending onboardings for admin review.
     */
    public function getPendingOnboardings(int $perPage = 20): LengthAwarePaginator
    {
        return $this->vendorRepository->getPendingOnboardings($perPage, ['user']);
    }

    /**
     * Check if user has completed onboarding.
     */
    public function hasApprovedOnboarding(int $userId): bool
    {
        return $this->vendorRepository->hasApprovedOnboarding($userId);
    }

    /**
     * Build onboarding data array from business info and bank details.
     * Filters out empty values to avoid overwriting with nulls.
     *
     * @param  array<string, mixed>  $businessInfo
     * @param  array<string, mixed>  $bankDetails
     * @return array<string, mixed>
     */
    private function buildOnboardingData(array $businessInfo, array $bankDetails): array
    {
        $data = [];

        $businessFields = ['legal_entity_name', 'business_category', 'tax_identification_number', 'physical_address'];
        foreach ($businessFields as $field) {
            if (isset($businessInfo[$field]) && $businessInfo[$field] !== '') {
                $data[$field] = $businessInfo[$field];
            }
        }

        $bankFields = ['bank_name', 'bank_branch', 'account_number', 'swift_bic_code'];
        foreach ($bankFields as $field) {
            if (isset($bankDetails[$field]) && $bankDetails[$field] !== '') {
                $data[$field] = $bankDetails[$field];
            }
        }

        return $data;
    }

    /**
     * Handle file uploads for onboarding.
     *
     * @param  array<string, UploadedFile|null>  $files
     */
    private function handleFileUploads(VendorOnboarding $onboarding, array $files): void
    {
        $fileFields = [
            'certificate_of_incorporation',
            'government_issued_id',
            'proof_of_address',
        ];

        $updates = [];
        foreach ($fileFields as $field) {
            if (isset($files[$field]) && $files[$field] instanceof UploadedFile) {
                // Delete old file if exists
                if ($onboarding->$field) {
                    Storage::disk('public')->delete($onboarding->$field);
                }

                $path = $files[$field]->store("vendor-onboarding/{$onboarding->user_id}", 'public');
                $updates[$field] = $path;
            }
        }

        if (! empty($updates)) {
            $this->vendorRepository->updateOnboarding($onboarding, $updates);
        }
    }

    /**
     * Validate onboarding submission.
     *
     * @param  array<string, mixed>  $businessInfo
     * @param  array<string, mixed>  $bankDetails
     * @param  array<string, UploadedFile|null>  $files
     * @return array<string, string>
     */
    private function validateOnboardingSubmission(
        array $businessInfo,
        array $bankDetails,
        bool $agreedToTerms,
        array $files = [],
        ?VendorOnboarding $existingOnboarding = null
    ): array {
        $errors = [];

        // Business info validation
        if (empty($businessInfo['legal_entity_name'])) {
            $errors['legal_entity_name'] = 'Legal entity name is required';
        }
        if (empty($businessInfo['business_category'])) {
            $errors['business_category'] = 'Business category is required';
        }
        if (empty($businessInfo['physical_address'])) {
            $errors['physical_address'] = 'Physical address is required';
        }

        // Bank details validation
        if (empty($bankDetails['bank_name'])) {
            $errors['bank_name'] = 'Bank name is required';
        }
        if (empty($bankDetails['bank_branch'])) {
            $errors['bank_branch'] = 'Bank branch is required';
        }
        if (empty($bankDetails['account_number'])) {
            $errors['account_number'] = 'Account number is required';
        }

        // File validation - check if new file uploaded or existing file present
        $hasNewCertificate = isset($files['certificate_of_incorporation']) && $files['certificate_of_incorporation'] instanceof UploadedFile;
        $hasExistingCertificate = $existingOnboarding?->certificate_of_incorporation;
        if (! $hasNewCertificate && ! $hasExistingCertificate) {
            $errors['certificate_of_incorporation'] = 'Certificate of incorporation is required';
        }

        $hasNewId = isset($files['government_issued_id']) && $files['government_issued_id'] instanceof UploadedFile;
        $hasExistingId = $existingOnboarding?->government_issued_id;
        if (! $hasNewId && ! $hasExistingId) {
            $errors['government_issued_id'] = 'Government-issued ID is required';
        }

        $hasNewProof = isset($files['proof_of_address']) && $files['proof_of_address'] instanceof UploadedFile;
        $hasExistingProof = $existingOnboarding?->proof_of_address;
        if (! $hasNewProof && ! $hasExistingProof) {
            $errors['proof_of_address'] = 'Proof of address is required';
        }

        if (! $agreedToTerms) {
            $errors['agreed_to_terms'] = 'You must agree to the terms and conditions';
        }

        return $errors;
    }
}
