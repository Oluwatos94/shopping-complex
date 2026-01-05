<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Services\Auth;

use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\ResetPasswordNotification;
use ModulesShoppingComplex\Repositories\UserRepository;

class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    /**
     * Send password reset link to the user
     *
     * @throws \Exception
     */
    public function sendResetLink(string $email): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            return false;
        }

        $token = $this->createToken($user);

        // Send notification
        $user->notify(new ResetPasswordNotification($token));

        return true;
    }

    /**
     * Create a password reset token for the user
     */
    protected function createToken(User $user): string
    {
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        $token = Str::random(64);

        // Store the token (will expire in 1 hour as per migration)
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        return $token;
    }

    /**
     * Reset the user's password
     *
     * @throws \Exception
     */
    public function resetPassword(string $email, string $token, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            return false;
        }

        if (! $this->validateToken($email, $token)) {
            return false;
        }

        DB::transaction(function () use ($user, $password) {
            $this->userRepository->update($user->id, [
                'password' => $password,
                'remember_token' => Str::random(60),
            ]);
        });

        $this->deleteToken($email);

        // Fire password reset event
        event(new PasswordReset($user->fresh()));

        return true;
    }

    protected function validateToken(string $email, string $token): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            return false;
        }

        // Check if token has expired (1 hour)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            $this->deleteToken($email);

            return false;
        }

        // Verify the token
        return Hash::check($token, $record->token);
    }

    protected function deleteToken(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    /**
     * Delete expired tokens (can be called by a scheduled command)
     */
    public function deleteExpiredTokens(): int
    {
        return DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subHour())
            ->delete();
    }
}
