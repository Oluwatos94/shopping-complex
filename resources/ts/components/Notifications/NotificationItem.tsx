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
  alert:   "#fca5a5",   // red
  success: "#86efac",   // green
  system:  "#c4b5fd",   // violet
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
      className={`group relative flex gap-3 px-4 py-[14px] border-b border-white/[0.06] transition-colors duration-150 ease-in-out hover:bg-white/[0.05] ${read ? "cursor-default bg-transparent" : "cursor-pointer bg-white/[0.03]"}`}
      style={{ borderLeft: `3px solid ${read ? "transparent" : color}` }}
    >
      {/* Type icon badge */}
      <div
        className="shrink-0 w-8 h-8 rounded-full flex items-center justify-center mt-0.5"
        style={{
          background: `${color}18`,
          border: `1px solid ${color}40`,
          color,
        }}
      >
        {typeIcon(type)}
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0">
        <div className="flex justify-between items-start gap-2">
          <p className={`m-0 text-[13px] font-mono tracking-[-0.01em] leading-[1.3] break-words ${read ? "font-normal text-white/65" : "font-semibold text-slate-100"}`}>
            {title}
          </p>
          <span className="text-[11px] text-white/35 whitespace-nowrap font-mono shrink-0">
            {relativeTime(timestamp)}
          </span>
        </div>
        <p className="mt-1 mb-0 text-xs text-white/45 font-mono leading-[1.5] break-words">
          {body}
        </p>
      </div>

      {/* Remove button — shown on row hover via group-hover */}
      <button
        onClick={handleRemove}
        title="Dismiss"
        className="absolute top-[10px] right-[10px] bg-transparent border-none text-white/25 cursor-pointer py-0.5 px-1 text-[14px] leading-none opacity-0 group-hover:opacity-100 transition-[opacity,color] duration-150 ease-in-out rounded hover:text-white/70"
      >
        ✕
      </button>

      {/* Unread dot */}
      {!read && (
        <div
          className="absolute top-[14px] right-[10px] w-1.5 h-1.5 rounded-full"
          style={{ background: color, boxShadow: `0 0 6px ${color}` }}
        />
      )}
    </div>
  );
};
