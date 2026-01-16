<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use ModulesShoppingComplex\Http\Requests\NotificationPreferenceRequest;
use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * PATCH /api/notifications/{notification}/read
     * Mark a single notification as read (JSON API)
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $this->notificationService->markAsRead($notification);

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * POST /api/notifications/mark-all-read
     * Mark all notifications as read (JSON API)
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }

    /**
     * DELETE /api/notifications/{notification}
     * Delete a notification (JSON API)
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->notificationService->delete($notification);

        return response()->json(['message' => 'Notification deleted']);
    }

    /**
     * GET /notifications/preferences
     * Notification preferences page (Inertia)
     */
    public function preferencesPage(Request $request): Response
    {
        $preferences = $this->notificationService->getUserPreferences($request->user());
        $types = config('notifications.types', []);

        return Inertia::render('Notifications/Preferences', [
            'preferences' => $preferences,
            'availableTypes' => $types,
        ]);
    }

    /**
     * POST /notifications/preferences/{type}
     * Update a specific notification preference (Inertia form)
     */
    public function updatePreference(
        NotificationPreferenceRequest $request,
        string $type
    ): RedirectResponse {
        // Validate type exists
        $types = config('notifications.types', []);
        if (! array_key_exists($type, $types)) {
            return back()->withErrors(['type' => 'Invalid notification type.']);
        }

        $this->notificationService->updatePreference(
            $request->user(),
            $type,
            $request->validated()
        );

        return back()->with('success', 'Notification preference updated.');
    }
}
