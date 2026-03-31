<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\UserRepository;

final readonly class ProfileService
{
    public function __construct(
        private UserRepository $userRepository,
        private MediaService $mediaService,
        private ChatService $chatService
    ) {}

    /**
     * Get profile data for display.
     *
     * @return array<string, mixed>
     */
    public function getProfileData(User $user): array
    {
        $avatar = $user->media()->where('type', 'avatar')->first();

        $conversations = $this->chatService->getConversations($user, 5);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'bio' => $user->bio,
                'role' => $user->role,
                'business_name' => $user->business_name,
                'avatar' => $avatar ? $this->mediaService->getMediaUrl($avatar) : null,
                'created_at' => $user->created_at->toISOString(),
            ],
            'conversations' => $conversations->items(),
        ];
    }

    /**
     * Update user profile fields.
     */
    public function updateProfile(User $user, array $data): User
    {
        return $this->userRepository->update($user->id, $data);
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $newPassword): User
    {
        return $this->userRepository->update($user->id, [
            'password' => Hash::make($newPassword),
        ]);
    }

    /**
     * Upload or replace user avatar.
     *
     * @return array{success: bool, avatar_url?: string, error?: string}
     */
    public function updateAvatar(User $user, UploadedFile $file): array
    {
        $existingAvatar = $user->media()->where('type', 'avatar')->first();
        if ($existingAvatar) {
            $this->mediaService->deleteMedia($existingAvatar->id);
        }

        $result = $this->mediaService->uploadImage(
            file: $file,
            modelType: User::class,
            modelId: $user->id,
            type: 'avatar'
        );

        if (! $result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Failed to upload avatar.'];
        }

        return [
            'success' => true,
            'avatar_url' => $this->mediaService->getMediaUrl($result['media']),
        ];
    }
}
