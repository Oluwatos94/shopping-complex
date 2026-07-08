<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Models\VendorOnboarding;
use ModulesShoppingComplex\Services\AdminAnalyticsService;
use ModulesShoppingComplex\Services\VendorService;

class AdminController extends Controller
{
    public function __construct(
        private readonly AdminAnalyticsService $adminAnalyticsService,
        private readonly VendorService $vendorService,
    ) {}

    public function stats(Request $request): Response|JsonResponse
    {
        $data = [
            ...$this->adminAnalyticsService->getPlatformStats(),
            'botStats' => $this->adminAnalyticsService->getPlatformBotStats(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Dashboard', $data);
    }

    public function settings(): Response
    {
        return Inertia::render('Admin/Settings');
    }

    public function botMonitor(Request $request): Response|JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 50), 1), 100);
        $data = ['interactions' => $this->adminAnalyticsService->getRecentInteractions($perPage)];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/BotMonitor', $data);
    }

    public function users(Request $request): Response|JsonResponse
    {
        $filters = $request->only(['role', 'search', 'per_page']);
        $data = [
            'users' => $this->adminAnalyticsService->getUserList($filters),
            'summary' => $this->adminAnalyticsService->getPlatformStats(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Users', $data);
    }

    public function subscriptions(Request $request): Response|JsonResponse
    {
        $filters = $request->only(['method', 'per_page']);
        $data = [
            'subscriptions' => $this->adminAnalyticsService->getPaidSubscriptions($filters),
            'stellarNetwork' => (string) config('services.stellar.network', 'testnet'),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Subscriptions', $data);
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

    public function viewVendorDocument(User $user, string $field): HttpResponse|\Illuminate\Http\RedirectResponse
    {
        $allowed = ['certificate_of_incorporation', 'government_issued_id', 'proof_of_address'];
        abort_if(! in_array($field, $allowed, true), 404);

        $onboarding = VendorOnboarding::where('user_id', $user->id)->firstOrFail();
        $path = $onboarding->$field;

        abort_if(! $path, 404);

        abort_if(! Storage::disk('local')->exists($path), 404);

        $mimeType = mime_content_type(Storage::disk('local')->path($path)) ?: 'application/octet-stream';

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

    public function pendingVendors(Request $request): Response|JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);
        $status = (string) $request->get('status', 'pending_review');
        $vendors = $this->adminAnalyticsService->getPendingVendors($perPage, $status);

        $data = [
            'vendors' => $vendors,
            'activeStatus' => $status,
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Vendors', $data);
    }

    public function approveVendor(User $user): \Illuminate\Http\RedirectResponse
    {
        try {
            $this->vendorService->approveOnboarding($user, Auth::user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        Log::info('Vendor application approved', [
            'vendor_id' => $user->id,
            'approved_by' => Auth::id(),
        ]);

        return back()->with('success', 'Vendor approved successfully.');
    }

    public function rejectVendor(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $this->vendorService->rejectOnboarding($user, Auth::user(), $validated['rejection_reason']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        Log::info('Vendor application rejected', [
            'vendor_id' => $user->id,
            'rejected_by' => Auth::id(),
            'reason' => $validated['rejection_reason'],
        ]);

        return back()->with('success', 'Vendor rejected successfully.');
    }
}
