import React, { useState, useCallback, useRef, useEffect } from "react";
import { useNotifications } from "../../hooks/useNotifications";
import { NotificationDropdown } from "./NotificationDropdown";

interface NotificationBellProps {
  /** WebSocket URL. Omit to use the built-in demo simulation. */
  wsUrl?: string;
}

/**
 * NotificationBell
 *
 * Drop this component anywhere in your app's header/navbar.
 * It manages its own state via `useNotifications` and renders
 * a badge-adorned bell that opens a real-time notification panel.
 *
 * Usage:
 *   <NotificationBell />
 *   <NotificationBell wsUrl="wss://yourapp.com/ws/notifications" />
 */
export const NotificationBell: React.FC<NotificationBellProps> = ({
  wsUrl,
}) => {
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  const {
    notifications,
    unreadCount,
    soundEnabled,
    connected,
    markAsRead,
    markAllAsRead,
    removeNotification,
    toggleSound,
  } = useNotifications({ wsUrl });

  const toggle = useCallback(() => setOpen((v) => !v), []);
  const close = useCallback(() => setOpen(false), []);

  // Close on outside click or Escape
  useEffect(() => {
    function onMouseDown(e: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        close();
      }
    }
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "Escape") close();
    }
    document.addEventListener("mousedown", onMouseDown);
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("mousedown", onMouseDown);
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [close]);

  // Pulse animation key: re-triggers when unread count rises
  const [pulseKey, setPulseKey] = useState(0);
  const prevUnread = useRef(unreadCount);
  useEffect(() => {
    if (unreadCount > prevUnread.current) {
      setPulseKey((k) => k + 1);
    }
    prevUnread.current = unreadCount;
  }, [unreadCount]);

  return (
    <div ref={containerRef} className="relative inline-flex">
        <button
          className="relative w-10 h-10 rounded-xl bg-white/[0.06] border border-white/10 text-white/75 cursor-pointer flex items-center justify-center transition-colors duration-150 ease-in-out outline-none hover:bg-white/10 hover:border-white/20 hover:text-slate-100 aria-expanded:bg-white/[0.12] aria-expanded:border-white/25 aria-expanded:text-slate-100"
          onClick={toggle}
          aria-label={`Notifications${unreadCount > 0 ? `, ${unreadCount} unread` : ""}`}
          aria-expanded={open}
          aria-haspopup="dialog"
        >
          {/* Ripple effect on new notification */}
          {pulseKey > 0 && (
            <span
              key={pulseKey}
              className="absolute inset-0 rounded-xl border border-red-500 pointer-events-none animate-ripple"
            />
          )}

          {/* Bell SVG */}
          <svg
            key={`bell-${pulseKey}`}
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={pulseKey > 0 ? "animate-bell-shake" : undefined}
          >
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
          </svg>

          {/* Badge */}
          {unreadCount > 0 && (
            <span
              key={`badge-${unreadCount}`}
              className="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-medium font-mono flex items-center justify-center border-2 border-[#0f1117] pointer-events-none leading-none animate-badge-pop"
            >
              {unreadCount > 99 ? "99+" : unreadCount}
            </span>
          )}
        </button>

        {/* Dropdown panel */}
        {open && (
          <NotificationDropdown
            notifications={notifications}
            soundEnabled={soundEnabled}
            connected={connected}
            onMarkAsRead={markAsRead}
            onMarkAllAsRead={markAllAsRead}
            onRemove={removeNotification}
            onToggleSound={toggleSound}
            onClose={close}
          />
        )}
      </div>
  );
};
