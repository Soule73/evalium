import { useState } from 'react';
import { BellIcon } from '@heroicons/react/24/outline';
import { usePage } from '@inertiajs/react';
import { type PageProps } from '@/types';
import { useNotifications } from '@/hooks/shared/useNotifications';
import { NotificationPanel } from './NotificationPanel';

/**
 * Bell icon button with unread badge that opens the NotificationPanel.
 * Fetches notifications lazily when the panel is first opened.
 */
export function NotificationBell() {
    const [isOpen, setIsOpen] = useState(false);
    const { notifications: sharedNotifications } = usePage<PageProps>().props;
    const unreadCountFromShared = sharedNotifications?.unread_count ?? 0;

    const {
        notifications,
        unreadCount,
        hasMore,
        loading,
        fetchNotifications,
        markRead,
        markAllRead,
    } = useNotifications();

    const displayUnread = isOpen ? unreadCount : unreadCountFromShared;

    const handleOpen = async () => {
        setIsOpen(true);
        await fetchNotifications();
    };

    const handleClose = () => {
        setIsOpen(false);
    };

    return (
        <>
            <button
                type="button"
                onClick={handleOpen}
                className="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label="Notifications"
            >
                <BellIcon className="w-5 h-5" />
                {displayUnread > 0 && (
                    <span className="absolute top-1 right-1 inline-flex items-center justify-center h-4 min-w-4 px-0.5 rounded-full text-xs font-bold bg-red-500 text-white leading-none">
                        {displayUnread > 99 ? '99+' : displayUnread}
                    </span>
                )}
            </button>

            <NotificationPanel
                isOpen={isOpen}
                onClose={handleClose}
                notifications={notifications}
                unreadCount={unreadCount}
                hasMore={hasMore}
                loading={loading}
                onMarkRead={markRead}
                onMarkAllRead={markAllRead}
            />
        </>
    );
}
