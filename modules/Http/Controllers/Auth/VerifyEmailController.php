<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Services\Auth\EmailVerificationService;

class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $emailVerificationService
    ) {}

    public function notice(): Response
    {
        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(Request $request): RedirectResponse
    {
        $userId = (int) $request->route('id');
        $hash = (string) $request->route('hash');

        // Validate the verification URL
        if (! $this->emailVerificationService->isValidVerificationUrl($userId, $hash)) {
            return redirect('/login')
                ->with('error', 'Invalid verification link.');
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect('/')->with('status', 'Email already verified!');
        }

        if ($this->emailVerificationService->verifyEmail($userId)) {
            event(new Verified($user));

            return redirect('/')->with('status', 'Email verified successfully!');
        }

        return redirect('/login')
            ->with('error', 'Email verification failed.');
    }

    /**
     * Resend the email verification notification
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect('/');
        }

        $this->emailVerificationService->sendVerificationEmail($user);

        return back()->with('status', 'Verification link sent!');
    }
}
