<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Events\SystemAlertEvent;
use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Repositories\UserRepository;
use ModulesShoppingComplex\Services\NotificationService;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
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
     * Send a welcome notification after login
     */
    public function sendWelcomeNotification(User $user, bool $isNewUser = false): void
    {
        $alreadySent = Notification::query()
            ->where('user_id', $user->id)
            ->where('group_key', 'system_alerts')
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($alreadySent) {
            return;
        }

        $message = $isNewUser
            ? 'Welcome to jiidaa, '.$user->name.'! Your account is ready.'
            : 'Welcome back, '.$user->name.'! You are now logged in.';

        $this->notificationService->send(new SystemAlertEvent(
            recipient: $user,
            message: $message,
            alertLevel: 'info',
        ));
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
        return DB::transaction(function () use ($providerId, $email, $name) {
            $user = $this->userRepository->findByGoogleId($providerId);

            if ($user) {
                return $user;
            }

            $user = $this->userRepository->findByEmail($email);

            if ($user) {
                $this->userRepository->update($user->id, [
                    'google_id' => $providerId,
                    'email_verified_at' => $user->email_verified_at ?? now(),
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
        });
    }
}
