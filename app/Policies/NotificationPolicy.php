<?php

declare(strict_types=1);

namespace App\Policies;

use ModulesShoppingComplex\Models\Notification;
use ModulesShoppingComplex\Models\User;

class NotificationPolicy
{
    /**
     * Determine if the user can view any notifications.
     */
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the notification.
     */
    public function view(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine if the user can update the notification (mark as read).
     */
    public function update(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    /**
     * Determine if the user can delete the notification.
     */
    public function delete(User $user, Notification $notification): bool
    {
        return $user->id === $notification->user_id;
    }
}
