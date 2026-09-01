<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Services\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Identity\Enums\UserEnum;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Repositories\UserRepository;
use ModulesShoppingComplex\Identity\Services\ReferralService;
use ModulesShoppingComplex\Notifications\AdminInvitationNotification;
use ModulesShoppingComplex\Notifications\Events\SystemAlertEvent;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\Services\NotificationService;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly ReferralService $referralService,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public function inviteAdmin(string $name, string $email, string $invitedBy): User
    {
        [$user, $token] = DB::transaction(function () use ($name, $email): array {
            $user = $this->userRepository->create([
                'name' => $name,
                'email' => $email,
                'role' => UserEnum::ADMIN->value,
                'password' => Str::random(64),
                'email_verified_at' => now(),
            ]);

            return [$user, $this->passwordResetService->createToken($user)];
        });

        $user->notify(new AdminInvitationNotification(
            token: $token,
            invitedBy: $invitedBy,
        ));

        return $user;
    }

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

        $user = $this->userRepository->create($data);

        $this->referralService->attachPendingReferral($user);

        Auth::login($user);

        return $user;
    }

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

    public function logout(): void
    {
        Auth::guard('web')->logout();
    }

    public function verifyEmail(int $userId): User
    {
        return $this->userRepository->verifyEmail($userId);
    }

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

            $user = $this->userRepository->create([
                'name' => $name,
                'email' => $email,
                'google_id' => $providerId,
                'role' => 'customer',
                'email_verified_at' => now(),
                'password' => Str::random(32),
            ]);

            $this->referralService->attachPendingReferral($user);

            return $user;
        });
    }
}
