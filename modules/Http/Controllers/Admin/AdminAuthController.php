<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\LoginRequest;

class AdminAuthController extends Controller
{
    public function showLoginForm(): Response
    {
        return Inertia::render('Admin/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Reject non-admin users immediately after authentication
        if (Auth::user()?->role !== 'admin') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our admin records.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin/dashboard');
    }
}
