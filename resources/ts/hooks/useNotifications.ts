import { useState, useEffect, useCallback, useRef } from 'react';
import Echo from '@/echo';
import { Notification, RawNotification, BroadcastPayload, NotificationType } from '@/types';

export type { Notification, NotificationType };

interface UseNotificationsOptions {
    userId?: number;
    soundEnabled?: boolean;
}

interface UseNotificationsReturn {
    notifications: Notification[];
    unreadCount: number;
    soundEnabled: boolean;
    connected: boolean;
    markAsRead: (id: string) => void;
    markAllAsRead: () => void;
    removeNotification: (id: string) => void;
    toggleSound: () => void;
}

const TYPE_TITLES: Record<string, string> = {
    message_received:      'New Message',
    vendor_contact_request:'Contact Request',
    product_updated:       'Product Update',
    system_alert:          'System Alert',
};

function titleForType(type: string): string {
    return TYPE_TITLES[type] ?? 'Notification';
}

function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext ||
            (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext)();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(880, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
        osc.start(ctx.currentTime);
        osc.stop(ctx.currentTime + 0.3);
    } catch {
        // Audio not available — silently ignore
    }
}

function mapRaw(raw: RawNotification): Notification {
    return {
        id:         raw.id,
        type:       raw.type,
        title:      titleForType(raw.type),
        body:       raw.message,
        timestamp:  new Date(raw.created_at),
        read:       raw.read,
        groupCount: raw.group_count,
        isGrouped:  raw.is_grouped,
        actionUrl:  (raw.data?.action_url as string | undefined),
    };
}


export function useNotifications(
    options: UseNotificationsOptions = {}
): UseNotificationsReturn {
    const { userId } = options;

    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [connected, setConnected]         = useState(false);
    const [soundEnabled, setSoundEnabled]   = useState(true);
    const soundRef = useRef(soundEnabled);

    useEffect(() => { soundRef.current = soundEnabled; }, [soundEnabled]);

    useEffect(() => {
        if (!userId) return;

        fetch('/api/notifications', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => r.json())
            .then((data: { notifications: RawNotification[] }) => {
                setNotifications(data.notifications.map(mapRaw));
            })
            .catch((err) => {
                if (import.meta.env.DEV) console.error('[Notifications] Failed to load:', err);
            });
    }, [userId]);

    useEffect(() => {
        if (!userId) return;

        const channel = Echo.private(`App.Models.User.${userId}`);

        // Use state_change to catch all transitions reliably
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const pusher = (Echo.connector as any)?.pusher;
        const onStateChange = ({ current }: { current: string }) => {
            setConnected(current === 'connected');
        };
        if (pusher) {
            pusher.connection.bind('state_change', onStateChange);
            setConnected(pusher.connection.state === 'connected');
        }

        const notificationTypes = [
            'message_received',
            'vendor_contact_request',
            'product_updated',
            'system_alert',
        ];

        notificationTypes.forEach((type) => {
            channel.listen(`.notification.${type}`, (payload: BroadcastPayload) => {
                const newNotif: Notification = {
                    id:        payload.id ?? `rt_${Date.now()}`,
                    type:      payload.type,
                    title:     titleForType(payload.type),
                    body:      payload.message,
                    timestamp: new Date(payload.created_at),
                    read:      false,
                    actionUrl: payload.data?.action_url as string | undefined,
                };

                setNotifications((prev) => [newNotif, ...prev].slice(0, 50));

                if (soundRef.current) playNotificationSound();
            });
        });

        return () => {
            if (pusher) pusher.connection.unbind('state_change', onStateChange);
            Echo.leave(`App.Models.User.${userId}`);
        };
    }, [userId]);


    const markAsRead = useCallback((id: string) => {
        setNotifications((prev) =>
            prev.map((n) => (n.id === id ? { ...n, read: true } : n))
        );

        fetch(`/api/notifications/${id}/read`, {
            method: 'PATCH',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
            credentials: 'same-origin',
        }).catch(() => {
            setNotifications((prev) =>
                prev.map((n) => (n.id === id ? { ...n, read: false } : n))
            );
        });
    }, []);

    const markAllAsRead = useCallback(() => {
        setNotifications((prev) => prev.map((n) => ({ ...n, read: true })));

        fetch('/api/notifications/mark-all-read', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
            credentials: 'same-origin',
        }).catch(() => {});
    }, []);

    const removeNotification = useCallback((id: string) => {
        setNotifications((prev) => prev.filter((n) => n.id !== id));

        fetch(`/api/notifications/${id}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': getCsrfToken() },
            credentials: 'same-origin',
        }).catch(() => {});
    }, []);

    const toggleSound = useCallback(() => setSoundEnabled((v) => !v), []);

    const unreadCount = notifications.filter((n) => !n.read).length;

    return {
        notifications,
        unreadCount,
        soundEnabled,
        connected,
        markAsRead,
        markAllAsRead,
        removeNotification,
        toggleSound,
    };
}


function getCsrfToken(): string {
    return (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
        ?.content ?? '';
}
