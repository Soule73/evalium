import { type AppNotification } from '@/types';
import { router } from '@inertiajs/react';
import { useTranslations } from '@/hooks';
import { formatDate } from '@/utils';
import { TrashIcon } from '@heroicons/react/24/outline';

interface NotificationItemProps {
    notification: AppNotification;
    onRead: (id: string) => void;
    onDelete: (id: string) => void;
}

/**
 * Renders a single notification row with read/unread state and navigation on click.
 */
export function NotificationItem({ notification, onRead, onDelete }: NotificationItemProps) {
    const { t } = useTranslations();

    const isUnread = notification.read_at === null;
    const data = notification.data;

    const labelMap: Record<string, string> = {
        assessment_published: t('notifications.types.assessment_published'),
        assessment_graded: t('notifications.types.assessment_graded'),
        assessment_submitted: t('notifications.types.assessment_submitted'),
        assessment_starting_soon: t('notifications.types.assessment_starting_soon'),
    };

    const handleClick = () => {
        if (isUnread) {
            onRead(notification.id);
        }
        router.visit(data.url);
    };

    return (
        <div
            className={`relative group flex items-start ${isUnread ? 'bg-blue-50' : 'bg-white'} hover:bg-gray-50 transition-colors`}
        >
            <button
                type="button"
                onClick={handleClick}
                className="flex-1 text-left px-4 py-3 flex gap-3 items-start min-w-0 cursor-pointer"
            >
                <span
                    className={`mt-1.5 shrink-0 h-2 w-2 rounded-full ${isUnread ? 'bg-blue-500' : 'bg-transparent'}`}
                />
                <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 truncate">
                        {labelMap[data.type] ?? data.type}
                    </p>
                    <p className="text-sm text-gray-600 truncate">{data.assessment_title}</p>
                    {data.subject && (
                        <p className="text-xs text-gray-400 truncate">{data.subject}</p>
                    )}
                </div>
                <span className="shrink-0 text-xs text-gray-400 whitespace-nowrap mt-0.5">
                    {formatDate(notification.created_at, 'datetime')}
                </span>
            </button>

            <button
                type="button"
                onClick={(e) => {
                    e.stopPropagation();
                    onDelete(notification.id);
                }}
                className="shrink-0 px-3 self-stretch flex items-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 cursor-pointer"
                aria-label={t('notifications.delete')}
            >
                <TrashIcon className="w-4 h-4" />
            </button>
        </div>
    );
}
