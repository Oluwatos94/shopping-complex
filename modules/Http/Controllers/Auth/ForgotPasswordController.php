<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Services\Auth\PasswordResetService;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    /**
     * Display the password reset request form
     */
    public function showLinkRequestForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Handle a password reset link request
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->ensureIsNotRateLimited($request);

        // Hit the rate limiter for all attempts (successful or not)
        RateLimiter::hit($this->throttleKey($request), 3600); // 1 hour decay

        $sent = $this->passwordResetService->sendResetLink($request->input('email'));

        if ($sent) {
            return back()->with('status', 'We have emailed your password reset link!');
        }

        throw ValidationException::withMessages([
            'email' => ['We could not find a user with that email address.'],
        ]);
    }

    /**
     * Ensure the password reset request is not rate limited
     *
     * Maximum 3 attempts per hour per email + IP combination
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request
     */
    protected function throttleKey(Request $request): string
    {
        return 'password-reset:'.strtolower($request->input('email')).'|'.$request->ip();
    }
}
