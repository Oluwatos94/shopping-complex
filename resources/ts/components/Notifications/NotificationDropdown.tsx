import React from "react";
import { Notification } from "@/types";
import { NotificationItem } from "./NotificationItem";

interface NotificationDropdownProps {
  notifications: Notification[];
  soundEnabled: boolean;
  onMarkAsRead: (id: string) => void;
  onMarkAllAsRead: () => void;
  onRemove: (id: string) => void;
  onToggleSound: () => void;
  onClose: () => void;
}

export const NotificationDropdown: React.FC<NotificationDropdownProps> = ({
  notifications,
  soundEnabled,
  onMarkAsRead,
  onMarkAllAsRead,
  onRemove,
  onToggleSound,
  onClose,
}) => {
  const unreadCount = notifications.filter((n) => !n.read).length;

  return (
    <>
      {/* Mobile backdrop */}
      <div
        className="fixed inset-0 bg-black/50 z-[9998] md:hidden"
        onClick={onClose}
        aria-hidden="true"
      />

      <div
        role="dialog"
        aria-label="Notifications"
        className={[
          "fixed left-0 right-0 top-[72px] z-[9999]",
          "md:absolute md:left-auto md:right-0 md:top-[calc(100%+12px)] md:w-[380px]",
          "bg-white border border-gray-200 rounded-b-2xl md:rounded-2xl overflow-hidden",
          "shadow-[0_16px_48px_rgba(0,0,0,0.5)] animate-dropdown-in",
        ].join(" ")}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-4 pt-4 pb-3 border-b border-gray-200">
          <div className="flex items-center gap-2">
            <span className="text-sm font-semibold text-primary-dark tracking-wide">
              Notifications
            </span>
          </div>

          <div className="flex items-center gap-2">
            {/* Sound toggle */}
            <button
              onClick={onToggleSound}
              title={soundEnabled ? "Mute" : "Unmute"}
              className={`border border-gray-300 rounded-lg p-1.5 transition-colors duration-150 ${
                soundEnabled
                  ? "text-primary-dark hover:border-primary-peach hover:text-primary-peach"
                  : "text-gray-300 hover:border-gray-400 hover:text-gray-500"
              }`}
            >
              {soundEnabled ? (
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5" />
                  <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07" />
                </svg>
              ) : (
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
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
                className="border border-gray-300 rounded-lg text-gray-500 py-1 px-2.5 text-xs transition-colors duration-150 whitespace-nowrap hover:border-primary-peach hover:text-primary-peach"
              >
                Mark all read
              </button>
            )}

            {/* Close — mobile only */}
            <button
              onClick={onClose}
              className="md:hidden border border-gray-300 rounded-lg p-1.5 text-gray-500 hover:text-primary-peach hover:border-primary-peach transition-colors duration-150"
              aria-label="Close notifications"
            >
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
        </div>

        {/* Notification list */}
        <div className="scrollbar-thin-dark max-h-[min(420px,55vh)] overflow-y-auto">
          {notifications.length === 0 ? (
            <div className="px-6 py-14 text-center">
              <p className="text-gray-400 text-sm">You're all caught up</p>
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
          <div className="px-4 py-3 border-t border-gray-200 text-center">
            <a
              href="/notifications/preferences"
              className="text-gray-400 text-xs tracking-wide hover:text-primary-peach transition-colors duration-150"
            >
              Notification settings
            </a>
          </div>
        )}
      </div>
    </>
  );
};
