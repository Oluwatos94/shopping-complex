<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use ModulesShoppingComplex\Services\Auth\AuthService;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
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

            $this->authService->sendWelcomeNotification($user);

            return redirect()->intended('/')
                ->with('success', 'Successfully logged in with Google!');

        } catch (Exception $e) {
            return redirect('/login')
                ->with('error', 'Failed to authenticate with Google. Please try again.');
        }
    }
}
