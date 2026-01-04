<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\UserRepository;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    /**
     * Register a new user
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User
    {
        // Default role to 'customer' if not provided
        if (! isset($data['role'])) {
            $data['role'] = 'customer';
        }

        // Create the user
        $user = $this->userRepository->create($data);

        // Log the user in immediately after registration
        Auth::login($user);

        return $user;
    }

    /**
     * Logout the authenticated user
     */
    public function logout(): void
    {
        Auth::guard('web')->logout();
    }

    /**
     * Verify user's email
     */
    public function verifyEmail(int $userId): User
    {
        return $this->userRepository->verifyEmail($userId);
    }

    /**
     * Handle social login (Google, Facebook, etc.)
     *
     * This method will:
     * 1. Check if user exists with the provider ID
     * 2. If not, check if user exists with the email
     * 3. If user exists with email, link the social account
     * 4. If user doesn't exist, create a new user
     */
    public function handleSocialLogin(
        string $provider,
        string $providerId,
        string $email,
        string $name,
        ?string $avatar = null
    ): User {
        $user = $this->userRepository->findByGoogleId($providerId);

        if ($user) {
            return $user;
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user) {
            // Link the social account to existing user
            $this->userRepository->update($user->id, [
                'google_id' => $providerId,
                'email_verified_at' => now(), // Auto-verify email for social login
            ]);

            return $user->fresh();
        }

        return $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'google_id' => $providerId,
            'role' => 'customer',
            'email_verified_at' => now(),
            'password' => Str::random(32),
        ]);
    }
}
