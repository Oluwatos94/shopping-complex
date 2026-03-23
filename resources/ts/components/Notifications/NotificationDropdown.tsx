import React from "react";
import { Notification } from "../../hooks/useNotifications";
import { NotificationItem } from "./NotificationItem";

interface NotificationDropdownProps {
  notifications: Notification[];
  soundEnabled: boolean;
  connected: boolean;
  onMarkAsRead: (id: string) => void;
  onMarkAllAsRead: () => void;
  onRemove: (id: string) => void;
  onToggleSound: () => void;
  onClose: () => void;
}

export const NotificationDropdown: React.FC<NotificationDropdownProps> = ({
  notifications,
  soundEnabled,
  connected,
  onMarkAsRead,
  onMarkAllAsRead,
  onRemove,
  onToggleSound,
}) => {

  const unreadCount = notifications.filter((n) => !n.read).length;

  return (
    <div
      role="dialog"
      aria-label="Notifications"
      style={{
        position: "absolute",
        top: "calc(100% + 12px)",
        right: 0,
        width: "clamp(320px, 90vw, 400px)",
        background: "#0f1117",
        border: "1px solid rgba(255,255,255,0.10)",
        borderRadius: "16px",
        boxShadow:
          "0 24px 64px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.04) inset",
        overflow: "hidden",
        zIndex: 9999,
        animation: "dropdownIn 0.2s cubic-bezier(0.16, 1, 0.3, 1)",
        fontFamily: "'DM Mono', monospace",
      }}
    >
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@300;400;500&display=swap');
        @keyframes dropdownIn {
          from { opacity: 0; transform: translateY(-8px) scale(0.97); }
          to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .notif-scroll::-webkit-scrollbar { width: 4px; }
        .notif-scroll::-webkit-scrollbar-track { background: transparent; }
        .notif-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
      `}</style>

      {/* Header */}
      <div
        style={{
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          padding: "16px 16px 12px",
          borderBottom: "1px solid rgba(255,255,255,0.08)",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
          <span
            style={{
              fontSize: "13px",
              fontWeight: 500,
              color: "#f1f5f9",
              letterSpacing: "-0.02em",
            }}
          >
            Notifications
          </span>
          {/* Connection indicator */}
          <span
            title={connected ? "Live" : "Disconnected"}
            style={{
              width: 6,
              height: 6,
              borderRadius: "50%",
              background: connected ? "#4ade80" : "#f87171",
              boxShadow: connected ? "0 0 6px #4ade80" : "none",
              display: "inline-block",
            }}
          />
        </div>

        <div style={{ display: "flex", alignItems: "center", gap: 4 }}>
          {/* Sound toggle */}
          <button
            onClick={onToggleSound}
            title={soundEnabled ? "Mute notifications" : "Unmute notifications"}
            style={{
              background: "none",
              border: "1px solid rgba(255,255,255,0.10)",
              borderRadius: 8,
              color: soundEnabled
                ? "rgba(255,255,255,0.65)"
                : "rgba(255,255,255,0.25)",
              cursor: "pointer",
              padding: "5px 8px",
              fontSize: "12px",
              display: "flex",
              alignItems: "center",
              gap: 4,
              transition: "all 0.15s ease",
            }}
          >
            {soundEnabled ? (
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" />
                <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07" />
              </svg>
            ) : (
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" />
                <line x1="23" y1="9" x2="17" y2="15" />
                <line x1="17" y1="9" x2="23" y2="15" />
              </svg>
            )}
          </button>

          {/* Mark all read */}
          {unreadCount > 0 && (
            <button
              onClick={onMarkAllAsRead}
              style={{
                background: "none",
                border: "1px solid rgba(255,255,255,0.10)",
                borderRadius: 8,
                color: "rgba(255,255,255,0.5)",
                cursor: "pointer",
                padding: "5px 10px",
                fontSize: "11px",
                fontFamily: "'DM Mono', monospace",
                transition: "all 0.15s ease",
                whiteSpace: "nowrap",
              }}
              onMouseEnter={(e) => {
                (e.currentTarget as HTMLButtonElement).style.color = "#f1f5f9";
                (e.currentTarget as HTMLButtonElement).style.borderColor =
                  "rgba(255,255,255,0.3)";
              }}
              onMouseLeave={(e) => {
                (e.currentTarget as HTMLButtonElement).style.color =
                  "rgba(255,255,255,0.5)";
                (e.currentTarget as HTMLButtonElement).style.borderColor =
                  "rgba(255,255,255,0.10)";
              }}
            >
              Mark all read
            </button>
          )}
        </div>
      </div>

      {/* Notification list */}
      <div
        className="notif-scroll"
        style={{
          maxHeight: "min(420px, 60vh)",
          overflowY: "auto",
        }}
      >
        {notifications.length === 0 ? (
          <div
            style={{
              padding: "48px 24px",
              textAlign: "center",
              color: "rgba(255,255,255,0.25)",
              fontSize: "13px",
            }}
          >
            <div style={{ fontSize: "32px", marginBottom: 12 }}>🔔</div>
            You're all caught up
          </div>
        ) : (
          notifications.map((n) => (
            <NotificationItem
              key={n.id}
              notification={n}
              onMarkAsRead={onMarkAsRead}
              onRemove={onRemove}
            />
          ))
        )}
      </div>

      {/* Footer */}
      {notifications.length > 0 && (
        <div
          style={{
            padding: "10px 16px",
            borderTop: "1px solid rgba(255,255,255,0.08)",
            textAlign: "center",
          }}
        >
          <button
            style={{
              background: "none",
              border: "none",
              color: "rgba(255,255,255,0.3)",
              cursor: "pointer",
              fontSize: "11px",
              fontFamily: "'DM Mono', monospace",
              letterSpacing: "0.04em",
              padding: "4px 8px",
              transition: "color 0.15s ease",
            }}
            onMouseEnter={(e) => {
              (e.currentTarget as HTMLButtonElement).style.color =
                "rgba(255,255,255,0.65)";
            }}
            onMouseLeave={(e) => {
              (e.currentTarget as HTMLButtonElement).style.color =
                "rgba(255,255,255,0.3)";
            }}
          >
            View notification settings
          </button>
        </div>
      )}
    </div>
  );
};