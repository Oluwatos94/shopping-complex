<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Shared\Repositories\BasePageRepository;

class NotificationRepository extends BasePageRepository
{
    /**
     * Get notifications for a user with pagination
     */
    public function getForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent notifications for a user (for shared Inertia data)
     *
     * @return Collection<int, Notification>
     */
    public function getRecentForUser(int $userId, int $limit = 10): Collection
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Count unread notifications for a user
     */
    public function countUnread(int $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification): bool
    {
        return $notification->update(['read_at' => now()]);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification
     */
    public function delete(Notification $notification): bool
    {
        return $notification->delete();
    }

    /**
     * Create a new notification
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * Create or update a grouped notification
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrUpdateGrouped(
        int $userId,
        string $type,
        string $message,
        array $data,
        string $groupKey
    ): Notification {
        $groupingWindowHours = config('notifications.grouping_window_hours', 24);

        return DB::transaction(function () use ($userId, $type, $message, $data, $groupKey, $groupingWindowHours) {
            $existing = Notification::query()
                ->where('user_id', $userId)
                ->where('group_key', $groupKey)
                ->whereNull('read_at')
                ->where('created_at', '>=', now()->subHours($groupingWindowHours))
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->update([
                    'message' => $message,
                    'data' => $data,
                    'group_count' => $existing->group_count + 1,
                    'is_grouped' => true,
                    'updated_at' => now(),
                ]);

                return $existing;
            }

            return Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
                'data' => $data,
                'group_key' => $groupKey,
                'is_grouped' => false,
                'group_count' => 1,
            ]);
        });
    }

    /**
     * Find a notification by ID
     */
    public function find(int $id): ?Notification
    {
        return Notification::find($id);
    }

    /**
     * Find a notification by ID for a specific user
     */
    public function findForUser(int $id, int $userId): ?Notification
    {
        return Notification::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }
}
