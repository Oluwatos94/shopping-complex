<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services;

use Illuminate\Support\Facades\Auth;
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
}
