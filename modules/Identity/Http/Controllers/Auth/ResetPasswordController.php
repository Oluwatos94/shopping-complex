<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Identity\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Identity\Http\Requests\ResetPasswordRequest;
use ModulesShoppingComplex\Identity\Services\Auth\PasswordResetService;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetService $passwordResetService
    ) {}

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $reset = $this->passwordResetService->resetPassword(
            email: $request->input('email'),
            token: $request->input('token'),
            password: $request->input('password')
        );

        if ($reset) {
            return redirect()->route('login')
                ->with('status', 'Your password has been reset successfully!');
        }

        throw ValidationException::withMessages([
            'email' => ['This password reset token is invalid or has expired.'],
        ]);
    }
}
