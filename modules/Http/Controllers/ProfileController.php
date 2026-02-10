<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\ChangePasswordRequest;
use ModulesShoppingComplex\Http\Requests\UpdateProfileRequest;
use ModulesShoppingComplex\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {}

    public function show(): Response
    {
        $data = $this->profileService->getProfileData(Auth::user());

        return Inertia::render('Profile/Index', $data);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->profileService->updateProfile(
            Auth::user(),
            $request->only(['name', 'email', 'phone', 'bio'])
        );

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $this->profileService->changePassword(Auth::user(), $request->input('password'));

        return back()->with('success', 'Password changed successfully.');
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $result = $this->profileService->updateAvatar(Auth::user(), $request->file('avatar'));

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json([
            'success' => true,
            'avatar_url' => $result['avatar_url'],
        ]);
    }
}
