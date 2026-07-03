<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;
use ModulesShoppingComplex\Models\Media;
use ModulesShoppingComplex\Models\Notification;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Cached notification data to avoid N+1 queries
     *
     * @var array<string, mixed>|null
     */
    private ?array $notificationCache = null;

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => fn () => $this->getAuthData($request),
            'notifications' => fn () => $this->getNotificationData($request)['notifications'],
            'unread_notifications_count' => fn () => $this->getNotificationData($request)['unread_count'],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
                'stellarCheckout' => fn () => $request->session()->get('stellarCheckout'),
            ],
        ]);
    }

    /**
     * Get authenticated user data including avatar for vendors.
     *
     * @return array{user: array<string, mixed>|null}
     */
    private function getAuthData(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return ['user' => null];
        }

        /** @var Media|null $avatar */
        $avatar = $user->media()->where('type', 'avatar')->first();

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'slug' => $user->slug,
            'avatar' => $avatar ? Storage::disk(config('media.storage_disk'))->url($avatar->url) : null,
        ];

        if ($user->role === 'vendor') {
            $data['business_name'] = $user->business_name;
            $data['business_logo'] = $data['avatar'];
        }

        return ['user' => $data];
    }

    /**
     * Get notification data with caching to avoid N+1 queries.
     * Fetches notifications and unread count in a single optimized query.
     *
     * @return array{notifications: array<int, array<string, mixed>>|null, unread_count: int|null}
     */
    private function getNotificationData(Request $request): array
    {
        if ($this->notificationCache !== null) {
            return $this->notificationCache;
        }

        $user = $request->user();

        if (! $user) {
            return $this->notificationCache = [
                'notifications' => null,
                'unread_count' => null,
            ];
        }

        $notifications = Notification::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Derive unread count from fetched notifications + count remaining unread beyond the 10
        $unreadInFetched = $notifications->whereNull('read_at')->count();
        $hasMoreUnread = $unreadInFetched === $notifications->count() && $notifications->count() === 10;

        $unreadCount = $hasMoreUnread
            ? Notification::query()->where('user_id', $user->id)->whereNull('read_at')->count()
            : $unreadInFetched;

        return $this->notificationCache = [
            'notifications' => $notifications->map(fn ($notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'message' => $notification->message,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toISOString(),
                'is_grouped' => $notification->is_grouped,
                'group_count' => $notification->group_count,
                'created_at' => $notification->created_at->toISOString(),
            ])->toArray(),
            'unread_count' => $unreadCount,
        ];
    }
}
