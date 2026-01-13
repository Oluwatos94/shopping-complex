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

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        // Temporarily unused for testing - will be used when email verification is re-enabled
        private readonly EmailVerificationService $emailVerificationService // @phpstan-ignore-line
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
    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended('/');
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
        // ORIGINAL IMPLEMENTATION - Commented out for testing
        // $validated = $request->validated();
        // $user = $this->authService->register($validated);
        // $this->emailVerificationService->sendVerificationEmail($user);
        // $request->session()->regenerate();
        // return redirect('/email/verify')
        //     ->with('status', 'Registration successful! Please verify your email address.');

        // TEMPORARY TESTING IMPLEMENTATION - Auto-login after registration
        $validated = $request->validated();
        $user = $this->authService->register($validated);

        // Auto-login the user for testing purposes
        auth()->login($user);
        $request->session()->regenerate();

        return redirect('/')
            ->with('success', 'Registration successful! You are now logged in.');
    }

    /**
     * Log the user out
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out successfully.');
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
