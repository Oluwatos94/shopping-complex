<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Models\Product;
use ModulesShoppingComplex\Models\User;
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

    public function products(Request $request): Response|JsonResponse
    {
        $filters = $request->only(['search', 'status', 'category', 'per_page']);
        $data = [
            'products'     => $this->adminAnalyticsService->getProducts($filters),
            'categories'   => $this->adminAnalyticsService->getCategories(),
            'totalPending' => Product::where('is_active', false)->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Admin/Products', $data);
    }

    public function updateProduct(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $product->update($validated);

        return response()->json(['message' => 'Product updated.', 'product' => $product]);
    }

    public function bulkApproveProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'integer|exists:products,id',
        ]);

        Product::whereIn('id', $validated['ids'])->update(['is_active' => true]);

        return response()->json(['message' => count($validated['ids']) . ' products approved.']);
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
            'users'   => $this->adminAnalyticsService->getUserList($filters),
            'summary' => $this->adminAnalyticsService->getPlatformStats(),
        ];

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
        try {
            $this->vendorService->approveOnboarding($user, Auth::user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
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

        try {
            $this->vendorService->rejectOnboarding($user, Auth::user(), $validated['rejection_reason']);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        Log::info('Vendor application rejected', [
            'vendor_id' => $user->id,
            'rejected_by' => Auth::id(),
            'reason' => $validated['rejection_reason'],
        ]);

        return response()->json(['message' => 'Vendor rejected successfully.']);
    }
}
