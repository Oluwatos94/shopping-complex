<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\LoginRequest;
use ModulesShoppingComplex\Http\Requests\RegisterRequest;
use ModulesShoppingComplex\Services\Auth\AuthService;
use ModulesShoppingComplex\Services\Auth\EmailVerificationService;
use ModulesShoppingComplex\Services\NotificationService;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly NotificationService $notificationService, // @phpstan-ignore-line
        private readonly EmailVerificationService $emailVerificationService
    ) {}

    /**
     * Display the login form
     */
    public function showLoginForm(): Response
    {
        return Inertia::render('auth/Login');
    }

    /**
     * Handle a login request
     */
    public function login(LoginRequest $request): SymfonyResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return Inertia::location('/email/verify');
        }

        $this->authService->sendWelcomeNotification($user);

        $intended = $request->session()->pull('url.intended', '/');

        return Inertia::location($intended);
    }

    /**
     * Display the registration form
     */
    public function showRegisterForm(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle a registration request
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $this->authService->register($validated);

        $this->emailVerificationService->sendVerificationEmail($user);
        $request->session()->regenerate();

        return redirect('/email/verify')
            ->with('status', 'Registration successful! Please check your email to verify your account.');
    }

    /**
     * Log the user out
     */
    public function logout(Request $request): SymfonyResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location('/');
    }

    /**
     * Get the authenticated user's data
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
