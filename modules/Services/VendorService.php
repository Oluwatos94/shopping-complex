<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Models\Address;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;
use ModulesShoppingComplex\Repositories\UserRepository;
use ModulesShoppingComplex\Repositories\VendorRepository;

final readonly class VendorService
{
    public function __construct(
        private VendorRepository $vendorRepository,
        private UserRepository $userRepository,
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
     * Get nearby vendors with pagination and filtering
     *
     * @param  array<string, mixed>  $filters
     */
    public function getNearbyVendors(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->vendorRepository->findNearby($filters, $perPage);
    }

    /**
     * Find nearby vendors matching a product name — used by the WhatsApp bot.
     *
     * @return Collection<int, User>
     */
    public function findNearbyByQuery(float $lat, float $lng, string $query, float $radiusKm = 5.0): Collection
    {
        return $this->getNearbyVendors([
            'latitude' => $lat,
            'longitude' => $lng,
            'radius' => $radiusKm,
            'search' => $query,
            'sort_by' => 'distance',
        ], perPage: 5)->getCollection();
    }

    /**
     * Find vendors matching a product name anywhere — used by the WhatsApp bot when no nearby vendors exist.
     *
     * @return Collection<int, User>
     */
    public function findByQuery(string $query, int $limit = 5): Collection
    {
        return $this->getNearbyVendors([
            'search' => $query,
            'sort_by' => 'newest',
        ], perPage: $limit)->getCollection();
    }

    /**
     * Find a vendor by ID — used by the WhatsApp bot.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getVendorById(int $vendorId): User
    {
        return $this->vendorRepository->find($vendorId);
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

            if (! empty($businessInfo['whatsapp_number'])) {
                $user->update(['whatsapp_number' => $businessInfo['whatsapp_number']]);
            }

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

            if (! empty($businessInfo['whatsapp_number'])) {
                $user->update(['whatsapp_number' => $businessInfo['whatsapp_number']]);
            }

            $onboarding = $this->vendorRepository->updateOrCreateOnboarding($user->id, $data);
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

            $this->vendorRepository->updateOnboarding($onboarding, [
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

            $this->vendorRepository->updateOnboarding($onboarding, [
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
     * Toggle follow/unfollow for a vendor.
     *
     * @return array{following: bool, followers_count: int}
     */
    public function toggleFollow(int $followerId, int $vendorId): array
    {
        $isFollowing = $this->userRepository->isFollowing($followerId, $vendorId);

        if ($isFollowing) {
            $this->userRepository->unfollowVendor($followerId, $vendorId);
        } else {
            $this->userRepository->followVendor($followerId, $vendorId);
        }

        return [
            'following' => ! $isFollowing,
            'followers_count' => $this->userRepository->getFollowersCount($vendorId),
        ];
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
        if (empty($businessInfo['whatsapp_number'])) {
            $errors['whatsapp_number'] = 'Business WhatsApp number is required';
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
