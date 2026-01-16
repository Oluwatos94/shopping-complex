<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Repositories;

use App\Repositories\BasePageRepository;
use Illuminate\Support\Collection;
use ModulesShoppingComplex\Models\NotificationPreference;

class NotificationPreferenceRepository extends BasePageRepository
{
    /**
     * Get all preferences for a user
     *
     * @return Collection<int, NotificationPreference>
     */
    public function getForUser(int $userId): Collection
    {
        return NotificationPreference::query()
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Get a specific preference for a user
     */
    public function get(int $userId, string $type): ?NotificationPreference
    {
        return NotificationPreference::query()
            ->where('user_id', $userId)
            ->where('notification_type', $type)
            ->first();
    }

    /**
     * Update or create a preference
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateOrCreate(int $userId, string $type, array $settings): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            ['user_id' => $userId, 'notification_type' => $type],
            $settings
        );
    }

    /**
     * Check if email is enabled for a notification type
     */
    public function isEmailEnabled(int $userId, string $type): bool
    {
        $preference = $this->get($userId, $type);

        if (! $preference) {
            // Return default from config
            $typeConfig = config("notifications.types.{$type}");

            return $typeConfig['default_email'] ?? true;
        }

        return $preference->email_enabled;
    }

    /**
     * Check if in-app notifications are enabled for a type
     */
    public function isInAppEnabled(int $userId, string $type): bool
    {
        $preference = $this->get($userId, $type);

        if (! $preference) {
            return true; // Default to enabled
        }

        return $preference->in_app_enabled;
    }

    /**
     * Delete all preferences for a user
     */
    public function deleteForUser(int $userId): int
    {
        return NotificationPreference::query()
            ->where('user_id', $userId)
            ->delete();
    }
}
