<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\URL;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\VerifyEmailNotification;
use ModulesShoppingComplex\Repositories\UserRepository;

class EmailVerificationService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    /**
     * Send email verification notification to the user
     */
    public function sendVerificationEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->notify(new VerifyEmailNotification);
    }

    /**
     * Verify the user's email address
     */
    public function verifyEmail(int $userId): bool
    {
        $user = $this->userRepository->find($userId);

        if ($user->hasVerifiedEmail()) {
            return false;
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            return true;
        }

        return false;
    }

    /**
     * Check if the email verification link is valid
     */
    public function isValidVerificationUrl(int $userId, string $hash): bool
    {
        $user = $this->userRepository->find($userId);

        // Check if hash matches user's email
        return hash_equals(
            (string) $hash,
            sha1($user->getEmailForVerification())
        );
    }

    /**
     * Generate a verification URL for the user
     */
    public function generateVerificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }
}
