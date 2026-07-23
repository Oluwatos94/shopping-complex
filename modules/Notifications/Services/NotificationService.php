<?php

declare(strict_types=1);

namespace ModulesShoppingComplex\Notifications\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use ModulesShoppingComplex\Models\User;
use ModulesShoppingComplex\Notifications\Events\BaseNotificationEvent;
use ModulesShoppingComplex\Notifications\Models\Notification;
use ModulesShoppingComplex\Notifications\Repositories\NotificationPreferenceRepository;
use ModulesShoppingComplex\Notifications\Repositories\NotificationRepository;
use ModulesShoppingComplex\Repositories\UserOnlineStatusRepository;

final readonly class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private NotificationPreferenceRepository $preferenceRepository,
        private UserOnlineStatusRepository $onlineStatusRepository,
        private NotificationEmailService $emailService,
    ) {}

    /**
     * Send a notification to a user
     */
    public function send(BaseNotificationEvent $event): ?Notification
    {
        $user = $event->recipient;
        $type = $event->getNotificationType();

        // Check user preferences
        if (! $this->shouldSendNotification($user, $type)) {
            return null;
        }

        $notification = $this->handleGrouping($event);

        event($event);

        if ($this->shouldSendEmailFallback($user, $type)) {
            $this->emailService->queueFallbackEmail($notification);
        }

        return $notification;
    }

    public function getUserNotifications(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->notificationRepository->getForUser($user->id, $perPage);
    }

    /**
     * Get recent notifications for a user (for Inertia shared data)
     *
     * @return Collection<int, Notification>
     */
    public function getRecentNotifications(User $user, int $limit = 10): Collection
    {
        return $this->notificationRepository->getRecentForUser($user->id, $limit);
    }

    public function getUnreadCount(User $user): int
    {
        return $this->notificationRepository->countUnread($user->id);
    }

    public function markAsRead(Notification $notification): bool
    {
        return $this->notificationRepository->markAsRead($notification);
    }

    public function markAllAsRead(User $user): int
    {
        return $this->notificationRepository->markAllAsRead($user->id);
    }

    public function delete(Notification $notification): bool
    {
        return $this->notificationRepository->delete($notification);
    }

    /**
     * Get user preferences with defaults
     *
     * @return array<string, mixed>
     */
    public function getUserPreferences(User $user): array
    {
        $preferences = $this->preferenceRepository->getForUser($user->id);
        $types = config('notifications.types', []);

        $result = [];

        foreach ($types as $type => $config) {
            $preference = $preferences->firstWhere('notification_type', $type);

            $result[$type] = [
                'label' => $config['label'],
                'description' => $config['description'],
                'email_enabled' => $preference !== null ? $preference->email_enabled : $config['default_email'],
                'push_enabled' => $preference !== null ? $preference->push_enabled : $config['default_push'],
                'in_app_enabled' => $preference !== null ? $preference->in_app_enabled : true,
            ];
        }

        return $result;
    }

    /**
     * Update user preference
     *
     * @param  array<string, bool>  $settings
     */
    public function updatePreference(User $user, string $type, array $settings): void
    {
        $this->preferenceRepository->updateOrCreate($user->id, $type, $settings);
    }

    /**
     * Update user online status
     */
    public function updateOnlineStatus(User $user, bool $isOnline, ?string $socketId = null): void
    {
        $this->onlineStatusRepository->update($user->id, $isOnline, $socketId);
    }

    /**
     * Check if notification should be sent based on user preferences
     */
    private function shouldSendNotification(User $user, string $type): bool
    {
        return $this->preferenceRepository->isInAppEnabled($user->id, $type);
    }

    /**
     * Check if email fallback should be sent
     */
    private function shouldSendEmailFallback(User $user, string $type): bool
    {
        if (! $this->preferenceRepository->isEmailEnabled($user->id, $type)) {
            return false;
        }

        // Send email if user hasn't been recently active
        return ! $this->onlineStatusRepository->wasRecentlyActive(
            $user->id,
            (int) config('notifications.email_fallback_delay_minutes', 5)
        );
    }

    /**
     * Handle notification grouping logic
     */
    private function handleGrouping(BaseNotificationEvent $event): Notification
    {
        $groupKey = $event->getGroupKey();

        if ($groupKey) {
            return $this->notificationRepository->createOrUpdateGrouped(
                userId: $event->recipient->id,
                type: $event->getNotificationType(),
                message: $event->message,
                data: $event->data,
                groupKey: $groupKey
            );
        }

        return $this->notificationRepository->create([
            'user_id' => $event->recipient->id,
            'type' => $event->getNotificationType(),
            'message' => $event->message,
            'data' => $event->data,
        ]);
    }
}
