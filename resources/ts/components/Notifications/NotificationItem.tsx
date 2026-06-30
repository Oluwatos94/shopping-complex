import React, { useCallback } from "react";
import { Notification, NotificationType } from "@/types";


const TYPE_ICONS: Record<string, React.ReactElement> = {
  message_received: (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
    </svg>
  ),
  vendor_contact_request: (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
      <circle cx="9" cy="7" r="4" />
      <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
      <path d="M16 3.13a4 4 0 0 1 0 7.75" />
    </svg>
  ),
  review_received: (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
    </svg>
  ),
  system_alert: (
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10" />
      <line x1="12" y1="8" x2="12" y2="12" />
      <line x1="12" y1="16" x2="12.01" y2="16" />
    </svg>
  ),
};

const TYPE_COLORS: Record<string, string> = {
  message_received:       "#d49f89",  // peach
  vendor_contact_request: "#86885e",  // olive
  review_received:        "#F5C518",  // star gold
  system_alert:           "#ffffff",  // white
};

const DEFAULT_ICON = (
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <circle cx="12" cy="12" r="3" />
    <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14" />
  </svg>
);

function typeIcon(type: NotificationType): React.ReactElement {
  return TYPE_ICONS[type] ?? DEFAULT_ICON;
}


function relativeTime(date: Date): string {
  const delta = Math.floor((Date.now() - date.getTime()) / 1000);
  if (delta < 60) return `${delta}s ago`;
  if (delta < 3600) return `${Math.floor(delta / 60)}m ago`;
  if (delta < 86400) return `${Math.floor(delta / 3600)}h ago`;
  return `${Math.floor(delta / 86400)}d ago`;
}


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
      className={`group relative flex gap-3 px-4 py-3.5 border-b border-gray-100 transition-colors duration-150 ease-in-out hover:bg-gray-50 ${read ? "cursor-default" : "cursor-pointer bg-blue-50/40"}`}
      style={{ borderLeft: `3px solid ${read ? "transparent" : color}` }}
    >
      {/* Type icon badge */}
      <div
        className="shrink-0 w-8 h-8 rounded-full flex items-center justify-center mt-0.5"
        style={{
          background: `${color}20`,
          border: `1px solid ${color}50`,
          color,
        }}
      >
        {typeIcon(type)}
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0 pr-4">
        <div className="flex justify-between items-start gap-2">
          <p className={`m-0 text-sm leading-snug break-words ${read ? "font-normal text-gray-400" : "font-semibold text-gray-800"}`}>
            {title}
          </p>
          <span className="text-xs text-gray-400 whitespace-nowrap shrink-0">
            {relativeTime(timestamp)}
          </span>
        </div>
        <p className="mt-1 mb-0 text-xs text-gray-500 leading-relaxed break-words">
          {body}
        </p>
      </div>

      <button
        onClick={handleRemove}
        title="Dismiss"
        className="absolute top-2.5 right-2.5 bg-transparent border-none text-gray-300 cursor-pointer p-1 leading-none opacity-0 group-hover:opacity-100 transition-[opacity,color] duration-150 rounded hover:text-primary-peach"
      >
        ✕
      </button>

      {/* Unread dot — hidden when remove button is visible on hover */}
      {!read && (
        <div
          className="absolute top-3.5 right-3 w-1.5 h-1.5 rounded-full group-hover:opacity-0 transition-opacity"
          style={{ background: color }}
        />
      )}
    </div>
  );
};
