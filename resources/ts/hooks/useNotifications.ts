import { useState, useEffect, useCallback, useRef } from "react";

export type NotificationType =
  | "message"
  | "mention"
  | "alert"
  | "success"
  | "system";

export interface Notification {
  id: string;
  type: NotificationType;
  title: string;
  body: string;
  timestamp: Date;
  read: boolean;
  avatarUrl?: string;
  actionUrl?: string;
}

interface UseNotificationsOptions {
  wsUrl?: string;
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

// Minimal audio beep via Web Audio API — no external assets needed
function playNotificationSound() {
  try {
    const ctx = new (window.AudioContext ||
      (window as unknown as { webkitAudioContext: typeof AudioContext })
        .webkitAudioContext)();
    const oscillator = ctx.createOscillator();
    const gainNode = ctx.createGain();

    oscillator.connect(gainNode);
    gainNode.connect(ctx.destination);

    oscillator.type = "sine";
    oscillator.frequency.setValueAtTime(880, ctx.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.1);

    gainNode.gain.setValueAtTime(0.3, ctx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);

    oscillator.start(ctx.currentTime);
    oscillator.stop(ctx.currentTime + 0.3);
  } catch {
    // Audio not available — silently ignore
  }
}

function generateId(): string {
  return `notif_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`;
}

/**
 * Simulates incoming WebSocket notifications for demo purposes.
 * Replace `simulateWebSocket` with a real WebSocket connection in production:
 *
 *   const ws = new WebSocket(wsUrl);
 *   ws.onmessage = (e) => addNotification(JSON.parse(e.data));
 */
function simulateWebSocket(
  onMessage: (n: Notification) => void,
  signal: AbortSignal
) {
  const templates: Omit<Notification, "id" | "timestamp" | "read">[] = [
    {
      type: "message",
      title: "New message from Alex",
      body: "Hey, are you available for a quick call?",
    },
    {
      type: "mention",
      title: "You were mentioned",
      body: "@you Left a comment on the design review thread.",
    },
    {
      type: "alert",
      title: "Deployment failed",
      body: "Production build #482 encountered an error in step 3.",
    },
    {
      type: "success",
      title: "Payment received",
      body: "Invoice #1042 has been paid — $240.00",
    },
    {
      type: "system",
      title: "System update",
      body: "Scheduled maintenance on Saturday 02:00–04:00 UTC.",
    },
  ];

  let timeoutId: ReturnType<typeof setTimeout>;

  function scheduleNext() {
    if (signal.aborted) return;
    const delay = 6000 + Math.random() * 10000;
    timeoutId = setTimeout(() => {
      if (signal.aborted) return;
      const template = templates[Math.floor(Math.random() * templates.length)];
      const message = { ...template, id: generateId(), timestamp: new Date(), read: false } as Notification;
      onMessage(message);
      scheduleNext();
    }, delay);
  }

  scheduleNext();
  return () => clearTimeout(timeoutId);
}

const SEED_NOTIFICATIONS: Notification[] = [
  {
    id: generateId(),
    type: "message",
    title: "Welcome to the platform",
    body: "Your account is all set up and ready to go.",
    timestamp: new Date(Date.now() - 1000 * 60 * 3),
    read: false,
  },
  {
    id: generateId(),
    type: "success",
    title: "Profile updated",
    body: "Your profile changes have been saved successfully.",
    timestamp: new Date(Date.now() - 1000 * 60 * 18),
    read: false,
  },
  {
    id: generateId(),
    type: "alert",
    title: "Login from new device",
    body: "We noticed a login from Chrome on Windows — Lagos, NG.",
    timestamp: new Date(Date.now() - 1000 * 60 * 60),
    read: true,
  },
];

export function useNotifications(
  options: UseNotificationsOptions = {}
): UseNotificationsReturn {
  const { wsUrl } = options;
  const [notifications, setNotifications] =
    useState<Notification[]>(SEED_NOTIFICATIONS);
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [connected, setConnected] = useState(false);
  const soundEnabledRef = useRef(soundEnabled);

  useEffect(() => {
    soundEnabledRef.current = soundEnabled;
  }, [soundEnabled]);

  const addNotification = useCallback((n: Notification) => {
    setNotifications((prev) => [n, ...prev].slice(0, 50));
    if (soundEnabledRef.current) playNotificationSound();
  }, []);

  // WebSocket / simulation setup
  useEffect(() => {
    const controller = new AbortController();

    if (wsUrl) {
      // Real WebSocket path
      const ws = new WebSocket(wsUrl);
      ws.onopen = () => setConnected(true);
      ws.onclose = () => setConnected(false);
      ws.onerror = () => setConnected(false);
      ws.onmessage = (e) => {
        try {
          const data = JSON.parse(e.data) as Notification;
          addNotification({ ...data, timestamp: new Date(data.timestamp) });
        } catch {
          /* ignore malformed messages */
        }
      };
      controller.signal.addEventListener("abort", () => ws.close());
    } else {
      // Demo simulation
      setConnected(true);
      // simulateWebSocket(addNotification, controller.signal);
    }

    return () => controller.abort();
  }, [wsUrl, addNotification]);

  const markAsRead = useCallback((id: string) => {
    setNotifications((prev) =>
      prev.map((n) => (n.id === id ? { ...n, read: true } : n))
    );
  }, []);

  const markAllAsRead = useCallback(() => {
    setNotifications((prev) => prev.map((n) => ({ ...n, read: true })));
  }, []);

  const removeNotification = useCallback((id: string) => {
    setNotifications((prev) => prev.filter((n) => n.id !== id));
  }, []);

  const toggleSound = useCallback(() => {
    setSoundEnabled((v) => !v);
  }, []);

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