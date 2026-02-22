import { useState } from 'react';
import { BellIcon } from '@heroicons/react/24/outline';
import { usePage } from '@inertiajs/react';
import { type PageProps } from '@/types';
import { useNotifications } from '@/hooks/shared/useNotifications';
import { NotificationPanel } from './NotificationPanel';
import { Button } from '@/Components/ui';

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
        deleteNotification,
        deleteAll,
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
            <Button type="button" variant="ghost" onClick={handleOpen}>
                <BellIcon className="w-5 h-5 cursor-pointer" />
                {displayUnread > 0 && (
                    <span className="absolute top-1 right-1 inline-flex items-center justify-center h-4 min-w-4 px-0.5 rounded-full text-xs font-bold bg-red-500 text-white leading-none">
                        {displayUnread > 99 ? '99+' : displayUnread}
                    </span>
                )}
            </Button>

            <NotificationPanel
                isOpen={isOpen}
                onClose={handleClose}
                notifications={notifications}
                unreadCount={unreadCount}
                hasMore={hasMore}
                loading={loading}
                onMarkRead={markRead}
                onMarkAllRead={markAllRead}
                onDelete={deleteNotification}
                onDeleteAll={deleteAll}
            />
        </>
    );
}
