<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use ModulesShoppingComplex\Identity\Models\User;
use ModulesShoppingComplex\Identity\Services\Auth\AuthService;
use ModulesShoppingComplex\Media\Services\MediaService;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Redirect to Google OAuth page
     */
    public function redirectToGoogle(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = $this->authService->handleSocialLogin(
                provider: 'google',
                providerId: $googleUser->getId(),
                email: $googleUser->getEmail(),
                name: $googleUser->getName(),
                avatar: $googleUser->getAvatar()
            );

            Auth::login($user);

            $this->maybeUploadAvatar($user, $googleUser->getAvatar());

            $this->authService->sendWelcomeNotification($user);

            return redirect()->intended('/')
                ->with('success', 'Successfully logged in with Google!');

        } catch (Exception $e) {
            return redirect('/login')
                ->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }

    /**
     * Download and store the Google avatar if the user doesn't have one yet.
     */
    private function maybeUploadAvatar(User $user, ?string $avatarUrl): void
    {
        if (! $avatarUrl || $user->media()->where('type', 'avatar')->exists()) {
            return;
        }

        $avatarUrl = preg_replace('/=s\d+-c/', '=s400-c', $avatarUrl) ?? $avatarUrl;

        try {
            $response = Http::timeout(10)->get($avatarUrl);

            if (! $response->successful()) {
                return;
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'google_avatar_');
            file_put_contents($tempPath, $response->body());

            $uploadedFile = new UploadedFile(
                path: $tempPath,
                originalName: 'avatar.jpg',
                mimeType: 'image/jpeg',
                error: null,
                test: true
            );

            $this->mediaService->uploadImage(
                file: $uploadedFile,
                modelType: User::class,
                modelId: $user->id,
                type: 'avatar'
            );

            @unlink($tempPath);
        } catch (Exception $e) {
            Log::warning('Failed to upload Google avatar', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
