import { XMarkIcon, BellIcon, CheckIcon, TrashIcon } from '@heroicons/react/24/outline';
import { type AppNotification } from '@/types';
import { NotificationItem } from './NotificationItem';
import { useTranslations } from '@/hooks';

interface NotificationPanelProps {
    isOpen: boolean;
    onClose: () => void;
    notifications: AppNotification[];
    unreadCount: number;
    loading: boolean;
    hasMore: boolean;
    onMarkRead: (id: string) => void;
    onMarkAllRead: () => Promise<void>;
    onDelete: (id: string) => void;
    onDeleteAll: () => void;
}

/**
 * Slide-over panel (right side, full height) listing user notifications.
 */
export function NotificationPanel({
    isOpen,
    onClose,
    notifications,
    unreadCount,
    loading,
    onMarkRead,
    onMarkAllRead,
    onDelete,
    onDeleteAll,
}: NotificationPanelProps) {
    const { t } = useTranslations();

    if (!isOpen) return null;

    return (
        <>
            <div className="fixed inset-0 z-100 bg-black/30" onClick={onClose} aria-hidden="true" />

            <div className="fixed top-0 right-0 z-150 h-screen w-full max-w-lg bg-white shadow-xl flex flex-col">
                <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 shrink-0">
                    <h2 className="text-base font-semibold text-gray-900">
                        {t('notifications.title')}
                        {unreadCount > 0 && (
                            <span className="ml-2 inline-flex items-center justify-center h-5 min-w-5 px-1 rounded-full text-xs font-medium bg-blue-600 text-white">
                                {unreadCount}
                            </span>
                        )}
                    </h2>

                    <div className="flex items-center gap-2">
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={onMarkAllRead}
                                className="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 transition-colors cursor-pointer"
                            >
                                <CheckIcon className="w-3.5 h-3.5" />
                                {t('notifications.mark_all_read')}
                            </button>
                        )}
                        {notifications.length > 0 && (
                            <button
                                type="button"
                                onClick={onDeleteAll}
                                className="inline-flex items-center gap-1 text-xs text-red-500 hover:text-red-600 transition-colors"
                            >
                                <TrashIcon className="w-3.5 h-3.5" />
                                {t('notifications.delete_all')}
                            </button>
                        )}
                        <button
                            type="button"
                            onClick={onClose}
                            className="text-gray-400 hover:text-gray-600 transition-colors"
                            aria-label={t('notifications.close')}
                        >
                            <XMarkIcon className="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto divide-y divide-gray-100">
                    {loading && notifications.length === 0 && (
                        <div className="flex flex-col gap-3 p-4">
                            {Array.from({ length: 5 }).map((_, i) => (
                                <div
                                    key={i}
                                    className="h-14 rounded-md bg-gray-100 animate-pulse"
                                />
                            ))}
                        </div>
                    )}

                    {!loading && notifications.length === 0 && (
                        <div className="flex flex-col items-center justify-center h-40 text-gray-400 gap-2">
                            <BellIcon className="w-8 h-8" />
                            <p className="text-sm">{t('notifications.empty')}</p>
                        </div>
                    )}

                    {notifications.map((notification) => (
                        <NotificationItem
                            key={notification.id}
                            notification={notification}
                            onRead={onMarkRead}
                            onDelete={onDelete}
                        />
                    ))}
                </div>

                {notifications.length === 0 && !loading && (
                    <div className="shrink-0 px-4 py-3 border-t border-gray-200" />
                )}
            </div>
        </>
    );
}
