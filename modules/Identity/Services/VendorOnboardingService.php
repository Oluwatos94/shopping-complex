<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Billing\Services\SubscriptionService;
use ModulesShoppingComplex\Identity\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Identity\Models\Address;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Models\VendorOnboarding;
use ModulesShoppingComplex\Identity\Repositories\OnboardingRepository;
use ModulesShoppingComplex\Media\Services\MediaService;

final readonly class VendorOnboardingService
{
    public function __construct(
        private OnboardingRepository $onboardingRepository,
        private MediaService $mediaService,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Register a customer as a vendor.
     *
     * @param  array{business_name: string, bio: string, category_id: int, whatsapp_number: string, address: string, city: string, state: string, latitude: string|float, longitude: string|float}  $data
     */
    public function registerAsVendor(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        return DB::transaction(function () use ($user, $data, $avatar) {
            $user->update([
                'role' => 'vendor',
                'business_name' => $data['business_name'],
                'slug' => Str::slug($data['business_name']).'-'.uniqid(),
                'bio' => $data['bio'],
                'category_id' => $data['category_id'],
                'whatsapp_number' => $data['whatsapp_number'],
            ]);

            Address::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'street' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'country' => 'Nigeria',
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                ]
            );

            if ($avatar) {

                $this->mediaService->deleteMediaByType(User::class, $user->id, 'avatar');
                $this->mediaService->uploadImage(
                    file: $avatar,
                    modelType: User::class,
                    modelId: $user->id,
                    type: 'avatar'
                );
            }

            return $user->fresh();
        });
    }

    /**
     * Get user's onboarding record.
     */
    public function getOnboarding(int $userId): ?VendorOnboarding
    {
        return $this->onboardingRepository->findOnboardingByUserId($userId);
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

            if (! empty($businessInfo['whatsapp_number'])) {
                $user->update(['whatsapp_number' => $businessInfo['whatsapp_number']]);
            }

            $onboarding = $this->onboardingRepository->updateOrCreateOnboarding($user->id, $data);
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

            $existingOnboarding = $this->onboardingRepository->findOnboardingByUserId($user->id);

            $errors = $this->validateOnboardingSubmission($files, $existingOnboarding);

            if (! empty($errors)) {
                throw new \InvalidArgumentException(json_encode($errors));
            }

            $data = $this->buildOnboardingData($businessInfo, $bankDetails);
            $data['agreed_to_terms'] = $agreedToTerms;
            $data['current_step'] = 4;
            $data['status'] = VendorOnboardingStatusEnum::PENDING_REVIEW;

            if (! empty($businessInfo['whatsapp_number'])) {
                $user->update(['whatsapp_number' => $businessInfo['whatsapp_number']]);
            }

            $onboarding = $this->onboardingRepository->updateOrCreateOnboarding($user->id, $data);
            $this->handleFileUploads($onboarding, $files);

            return $onboarding->fresh();
        });
    }

    /**
     * Approve vendor onboarding (admin).
     * Assigns the Free plan to the vendor on approval.
     *
     * @throws \RuntimeException if no pending application exists
     */
    public function approveOnboarding(User $vendor, User $reviewer): VendorOnboarding
    {
        return DB::transaction(function () use ($vendor, $reviewer) {
            $onboarding = VendorOnboarding::where('user_id', $vendor->id)
                ->lockForUpdate()
                ->first();

            if (! $onboarding) {
                throw new \RuntimeException('No application found for this vendor.');
            }

            $this->onboardingRepository->updateOnboarding($onboarding, [
                'status' => VendorOnboardingStatusEnum::APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->subscriptionService->assignFreePlan($vendor->id);

            return $onboarding->fresh();
        });
    }

    /**
     * Reject vendor onboarding (admin).
     *
     * @throws \RuntimeException if no pending application exists
     */
    public function rejectOnboarding(User $vendor, User $reviewer, string $reason): VendorOnboarding
    {
        return DB::transaction(function () use ($vendor, $reviewer, $reason) {
            $onboarding = VendorOnboarding::where('user_id', $vendor->id)
                ->lockForUpdate()
                ->first();

            if (! $onboarding) {
                throw new \RuntimeException('No application found for this vendor.');
            }

            $this->onboardingRepository->updateOnboarding($onboarding, [
                'status' => VendorOnboardingStatusEnum::REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $onboarding->fresh();
        });
    }

    /**
     * Get pending onboardings for admin review.
     */
    public function getPendingOnboardings(int $perPage = 20): LengthAwarePaginator
    {
        return $this->onboardingRepository->getPendingOnboardings($perPage, ['user']);
    }

    /**
     * Check if user has completed onboarding.
     */
    public function hasApprovedOnboarding(int $userId): bool
    {
        return $this->onboardingRepository->hasApprovedOnboarding($userId);
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
                    Storage::disk('local')->delete($onboarding->$field);
                }

                $path = $files[$field]->store("vendor-onboarding/{$onboarding->user_id}", 'local');
                $updates[$field] = $path;
            }
        }

        if (! empty($updates)) {
            $this->onboardingRepository->updateOnboarding($onboarding, $updates);
        }
    }

    /**
     * Validate that required documents exist (new upload or already on file).
     * Field-level validation is handled by SubmitOnboardingRequest.
     *
     * @param  array<string, UploadedFile|null>  $files
     * @return array<string, string>
     */
    private function validateOnboardingSubmission(
        array $files = [],
        ?VendorOnboarding $existingOnboarding = null
    ): array {
        $errors = [];

        $required = [
            'certificate_of_incorporation' => 'Certificate of incorporation is required',
            'government_issued_id' => 'Government-issued ID is required',
            'proof_of_address' => 'Proof of address is required',
        ];

        foreach ($required as $field => $message) {
            $hasNew = isset($files[$field]) && $files[$field] instanceof UploadedFile;
            $hasExisting = (bool) $existingOnboarding?->$field;
            if (! $hasNew && ! $hasExisting) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }
}
