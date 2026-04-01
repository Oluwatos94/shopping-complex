<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\Enums\VendorOnboardingStatusEnum;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;
use ModulesShoppingComplex\Services\AdminAnalyticsService;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminAnalyticsService $adminAnalyticsService
    ) {}

    public function stats(Request $request): Response|JsonResponse
    {
        $data = $this->adminAnalyticsService->getPlatformStats();

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Dashboard', $data);
    }

    public function users(Request $request): Response|JsonResponse
    {
        $filters = $request->only(['role', 'search', 'per_page']);
        $data = ['users' => $this->adminAnalyticsService->getUserList($filters)];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Users', $data);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,customer,vendor',
        ]);

        $user->update($validated);
        $user->refresh();

        Log::info('Admin changed user role', [
            'target_user_id' => $user->id,
            'new_role' => $user->role,
            'changed_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
        ]);
    }

    public function pendingVendors(Request $request): Response|JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        $data = ['vendors' => $this->adminAnalyticsService->getPendingVendors($perPage)];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Vendors', $data);
    }

    public function approveVendor(User $user): JsonResponse
    {
        $approved = DB::transaction(function () use ($user) {
            $onboarding = VendorOnboarding::where('user_id', $user->id)
                ->where('status', VendorOnboardingStatusEnum::PENDING_REVIEW)
                ->lockForUpdate()
                ->first();

            if (! $onboarding) {
                return false;
            }

            $onboarding->update([
                'status' => VendorOnboardingStatusEnum::APPROVED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            return true;
        });

        if (! $approved) {
            return response()->json(['message' => 'No pending application found for this vendor.'], 422);
        }

        Log::info('Vendor application approved', [
            'vendor_id' => $user->id,
            'approved_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Vendor approved successfully.']);
    }

    public function rejectVendor(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $rejected = DB::transaction(function () use ($user, $validated) {
            $onboarding = VendorOnboarding::where('user_id', $user->id)
                ->where('status', VendorOnboardingStatusEnum::PENDING_REVIEW)
                ->lockForUpdate()
                ->first();

            if (! $onboarding) {
                return false;
            }

            $onboarding->update([
                'status' => VendorOnboardingStatusEnum::REJECTED,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            return true;
        });

        if (! $rejected) {
            return response()->json(['message' => 'No pending application found for this vendor.'], 422);
        }

        Log::info('Vendor application rejected', [
            'vendor_id' => $user->id,
            'rejected_by' => Auth::id(),
            'reason' => $validated['rejection_reason'],
        ]);

        return response()->json(['message' => 'Vendor rejected successfully.']);
    }
}
