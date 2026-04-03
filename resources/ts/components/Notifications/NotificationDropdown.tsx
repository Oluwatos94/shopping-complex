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
      className="absolute right-0 w-[clamp(320px,90vw,400px)] bg-[#0f1117] border border-white/10 rounded-2xl overflow-hidden z-[9999] font-mono shadow-[0_24px_64px_rgba(0,0,0,0.7),0_0_0_1px_rgba(255,255,255,0.04)_inset] animate-dropdown-in"
      style={{ top: "calc(100% + 12px)" }}
    >

      {/* Header */}
      <div className="flex items-center justify-between px-4 pt-4 pb-3 border-b border-white/[0.08]">
        <div className="flex items-center gap-2">
          <span className="text-[13px] font-medium text-slate-100 tracking-[-0.02em]">
            Notifications
          </span>
          {/* Connection indicator */}
          <span
            title={connected ? "Live" : "Disconnected"}
            className={`inline-block w-1.5 h-1.5 rounded-full ${connected ? "bg-green-400" : "bg-red-400"}`}
            style={connected ? { boxShadow: "0 0 6px #4ade80" } : undefined}
          />
        </div>

        <div className="flex items-center gap-1">
          {/* Sound toggle */}
          <button
            onClick={onToggleSound}
            title={soundEnabled ? "Mute notifications" : "Unmute notifications"}
            className={`bg-transparent border border-white/10 rounded-lg cursor-pointer py-[5px] px-2 text-xs flex items-center gap-1 transition-all duration-150 ease-in-out ${soundEnabled ? "text-white/65" : "text-white/25"}`}
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
              className="bg-transparent border border-white/10 rounded-lg text-white/50 cursor-pointer py-[5px] px-[10px] text-[11px] font-mono transition-all duration-150 ease-in-out whitespace-nowrap hover:text-slate-100 hover:border-white/30"
            >
              Mark all read
            </button>
          )}
        </div>
      </div>

      {/* Notification list */}
      <div className="scrollbar-thin-dark max-h-[min(420px,60vh)] overflow-y-auto">
        {notifications.length === 0 ? (
          <div className="px-6 py-12 text-center text-white/25 text-[13px]">
            <div className="text-[32px] mb-3">🔔</div>
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
        <div className="px-4 py-[10px] border-t border-white/[0.08] text-center">
          <button className="bg-transparent border-none text-white/30 cursor-pointer text-[11px] font-mono tracking-[0.04em] py-1 px-2 transition-colors duration-150 ease-in-out hover:text-white/65">
            View notification settings
          </button>
        </div>
      )}
    </div>
  );
};
