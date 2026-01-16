<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use ModulesShoppingComplex\Models\UserOnlineStatus;

class UserOnlineStatusRepository extends BasePageRepository
{
    /**
     * Get online status for a user
     */
    public function get(int $userId): ?UserOnlineStatus
    {
        return UserOnlineStatus::query()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Update or create online status
     */
    public function update(int $userId, bool $isOnline, ?string $socketId = null): UserOnlineStatus
    {
        return UserOnlineStatus::updateOrCreate(
            ['user_id' => $userId],
            [
                'is_online' => $isOnline,
                'last_seen_at' => now(),
                'socket_id' => $socketId,
            ]
        );
    }

    public function setOnline(int $userId, ?string $socketId = null): UserOnlineStatus
    {
        return $this->update($userId, true, $socketId);
    }

    public function setOffline(int $userId): UserOnlineStatus
    {
        return $this->update($userId, false, null);
    }

    public function isOnline(int $userId): bool
    {
        $status = $this->get($userId);

        if ($status === null) {
            return false;
        }

        return $status->is_online;
    }

    public function wasRecentlyActive(int $userId, int $minutes = 5): bool
    {
        $status = $this->get($userId);

        if (! $status) {
            return false;
        }

        return $status->is_online || ($status->last_seen_at !== null && $status->last_seen_at->gt(now()->subMinutes($minutes)));
    }

    public function updateLastSeen(int $userId): void
    {
        UserOnlineStatus::query()
            ->where('user_id', $userId)
            ->update(['last_seen_at' => now()]);
    }
}
