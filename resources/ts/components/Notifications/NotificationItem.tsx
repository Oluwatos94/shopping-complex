import React, { useCallback } from "react";
import { Notification, NotificationType } from "../../hooks/useNotifications";

// ─── Icon helpers ────────────────────────────────────────────────────────────

function typeIcon(type: NotificationType) {
  const icons: Record<NotificationType, React.ReactElement> = {
    message: (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
      </svg>
    ),
    mention: (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="4" />
        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94" />
      </svg>
    ),
    alert: (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
        <line x1="12" y1="9" x2="12" y2="13" />
        <line x1="12" y1="17" x2="12.01" y2="17" />
      </svg>
    ),
    success: (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <polyline points="20 6 9 17 4 12" />
      </svg>
    ),
    system: (
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="3" />
        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14" />
      </svg>
    ),
  };
  return icons[type];
}

const TYPE_COLORS: Record<NotificationType, string> = {
  message: "#6ee7b7",   // emerald
  mention: "#93c5fd",   // blue
  alert: "#fca5a5",     // red
  success: "#86efac",   // green
  system: "#c4b5fd",    // violet
};

// ─── Relative timestamp ──────────────────────────────────────────────────────

function relativeTime(date: Date): string {
  const delta = Math.floor((Date.now() - date.getTime()) / 1000);
  if (delta < 60) return `${delta}s ago`;
  if (delta < 3600) return `${Math.floor(delta / 60)}m ago`;
  if (delta < 86400) return `${Math.floor(delta / 3600)}h ago`;
  return `${Math.floor(delta / 86400)}d ago`;
}

// ─── Component ───────────────────────────────────────────────────────────────

interface NotificationItemProps {
  notification: Notification;
  onMarkAsRead: (id: string) => void;
  onRemove: (id: string) => void;
}

export const NotificationItem: React.FC<NotificationItemProps> = ({
  notification,
  onMarkAsRead,
  onRemove,
}) => {
  const { id, type, title, body, timestamp, read } = notification;
  const color = TYPE_COLORS[type];

  const handleClick = useCallback(() => {
    if (!read) onMarkAsRead(id);
  }, [id, read, onMarkAsRead]);

  const handleRemove = useCallback(
    (e: React.MouseEvent) => {
      e.stopPropagation();
      onRemove(id);
    },
    [id, onRemove]
  );

  return (
    <div
      onClick={handleClick}
      style={{
        display: "flex",
        gap: "12px",
        padding: "14px 16px",
        cursor: read ? "default" : "pointer",
        background: read ? "transparent" : "rgba(255,255,255,0.03)",
        borderLeft: `3px solid ${read ? "transparent" : color}`,
        borderBottom: "1px solid rgba(255,255,255,0.06)",
        transition: "background 0.15s ease",
        position: "relative",
      }}
      onMouseEnter={(e) => {
        (e.currentTarget as HTMLDivElement).style.background =
          "rgba(255,255,255,0.05)";
      }}
      onMouseLeave={(e) => {
        (e.currentTarget as HTMLDivElement).style.background = read
          ? "transparent"
          : "rgba(255,255,255,0.03)";
      }}
    >
      {/* Type icon badge */}
      <div
        style={{
          flexShrink: 0,
          width: 32,
          height: 32,
          borderRadius: "50%",
          background: `${color}18`,
          border: `1px solid ${color}40`,
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          color,
          marginTop: 2,
        }}
      >
        {typeIcon(type)}
      </div>

      {/* Content */}
      <div style={{ flex: 1, minWidth: 0 }}>
        <div
          style={{
            display: "flex",
            justifyContent: "space-between",
            alignItems: "flex-start",
            gap: 8,
          }}
        >
          <p
            style={{
              margin: 0,
              fontSize: "13px",
              fontWeight: read ? 400 : 600,
              color: read ? "rgba(255,255,255,0.65)" : "#f1f5f9",
              fontFamily: "'DM Mono', monospace",
              letterSpacing: "-0.01em",
              lineHeight: 1.3,
              wordBreak: "break-word",
            }}
          >
            {title}
          </p>
          <span
            style={{
              fontSize: "11px",
              color: "rgba(255,255,255,0.35)",
              whiteSpace: "nowrap",
              fontFamily: "'DM Mono', monospace",
              flexShrink: 0,
            }}
          >
            {relativeTime(timestamp)}
          </span>
        </div>
        <p
          style={{
            margin: "4px 0 0",
            fontSize: "12px",
            color: "rgba(255,255,255,0.45)",
            fontFamily: "'DM Mono', monospace",
            lineHeight: 1.5,
            wordBreak: "break-word",
          }}
        >
          {body}
        </p>
      </div>

      {/* Remove button */}
      <button
        onClick={handleRemove}
        title="Dismiss"
        style={{
          position: "absolute",
          top: 10,
          right: 10,
          background: "none",
          border: "none",
          color: "rgba(255,255,255,0.25)",
          cursor: "pointer",
          padding: "2px 4px",
          fontSize: "14px",
          lineHeight: 1,
          opacity: 0,
          transition: "opacity 0.15s ease, color 0.15s ease",
          borderRadius: 4,
        }}
        className="notif-remove-btn"
        onMouseEnter={(e) => {
          (e.currentTarget as HTMLButtonElement).style.color =
            "rgba(255,255,255,0.7)";
        }}
        onMouseLeave={(e) => {
          (e.currentTarget as HTMLButtonElement).style.color =
            "rgba(255,255,255,0.25)";
        }}
      >
        ✕
      </button>

      {/* Unread dot */}
      {!read && (
        <div
          style={{
            position: "absolute",
            top: 14,
            right: 10,
            width: 6,
            height: 6,
            borderRadius: "50%",
            background: color,
            boxShadow: `0 0 6px ${color}`,
          }}
        />
      )}

      <style>{`
        div:hover .notif-remove-btn { opacity: 1 !important; }
      `}</style>
    </div>
  );
};