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
    <>
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@300;400;500&display=swap');

        @keyframes bellShake {
          0%, 100% { transform: rotate(0deg); }
          15%       { transform: rotate(-12deg); }
          30%       { transform: rotate(12deg); }
          45%       { transform: rotate(-8deg); }
          60%       { transform: rotate(8deg); }
          75%       { transform: rotate(-4deg); }
          90%       { transform: rotate(4deg); }
        }

        @keyframes badgePop {
          0%   { transform: scale(0.6); opacity: 0; }
          60%  { transform: scale(1.15); opacity: 1; }
          100% { transform: scale(1);   opacity: 1; }
        }

        @keyframes ripple {
          0%   { transform: scale(1); opacity: 0.6; }
          100% { transform: scale(2.4); opacity: 0; }
        }

        .notif-bell-btn {
          position: relative;
          width: 40px;
          height: 40px;
          border-radius: 12px;
          background: rgba(255,255,255,0.06);
          border: 1px solid rgba(255,255,255,0.10);
          color: rgba(255,255,255,0.75);
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
          outline: none;
        }

        .notif-bell-btn:hover {
          background: rgba(255,255,255,0.10);
          border-color: rgba(255,255,255,0.20);
          color: #f1f5f9;
        }

        .notif-bell-btn[aria-expanded="true"] {
          background: rgba(255,255,255,0.12);
          border-color: rgba(255,255,255,0.25);
          color: #f1f5f9;
        }

        .notif-bell-icon.shaking {
          animation: bellShake 0.6s ease;
        }

        .notif-badge {
          position: absolute;
          top: -4px;
          right: -4px;
          min-width: 18px;
          height: 18px;
          padding: 0 4px;
          border-radius: 9px;
          background: #ef4444;
          color: #fff;
          font-family: 'DM Mono', monospace;
          font-size: 10px;
          font-weight: 500;
          display: flex;
          align-items: center;
          justify-content: center;
          border: 2px solid #0f1117;
          animation: badgePop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
          pointer-events: none;
          line-height: 1;
        }

        .notif-ripple {
          position: absolute;
          inset: 0;
          border-radius: 12px;
          border: 1px solid #ef4444;
          animation: ripple 0.6s ease-out forwards;
          pointer-events: none;
        }
      `}</style>

      <div ref={containerRef} style={{ position: "relative", display: "inline-flex" }}>
        <button
          className="notif-bell-btn"
          onClick={toggle}
          aria-label={`Notifications${unreadCount > 0 ? `, ${unreadCount} unread` : ""}`}
          aria-expanded={open}
          aria-haspopup="dialog"
        >
          {/* Ripple effect on new notification */}
          {pulseKey > 0 && <span key={pulseKey} className="notif-ripple" />}

          {/* Bell SVG */}
          <svg
            className={`notif-bell-icon${pulseKey > 0 ? " shaking" : ""}`}
            key={`bell-${pulseKey}`}
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.75"
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
            <path d="M13.73 21a2 2 0 0 1-3.46 0" />
          </svg>

          {/* Badge */}
          {unreadCount > 0 && (
            <span key={`badge-${unreadCount}`} className="notif-badge">
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
    </>
  );
};