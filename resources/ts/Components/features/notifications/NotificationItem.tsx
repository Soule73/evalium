import { type AppNotification } from '@/types';
import { router } from '@inertiajs/react';
import { useTranslations } from '@/hooks';
import { formatDate } from '@/utils';

interface NotificationItemProps {
    notification: AppNotification;
    onRead: (id: string) => void;
}

/**
 * Renders a single notification row with read/unread state and navigation on click.
 */
export function NotificationItem({ notification, onRead }: NotificationItemProps) {
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
        <button
            type="button"
            onClick={handleClick}
            className={`w-full text-left px-4 py-3 transition-colors hover:bg-gray-50 flex gap-3 items-start ${
                isUnread ? 'bg-blue-50' : 'bg-white'
            }`}
        >
            <span
                className={`mt-1.5 shrink-0 h-2 w-2 rounded-full ${isUnread ? 'bg-blue-500' : 'bg-transparent'}`}
            />
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-900 truncate">
                    {labelMap[data.type] ?? data.type}
                </p>
                <p className="text-sm text-gray-600 truncate">{data.assessment_title}</p>
                {data.subject && <p className="text-xs text-gray-400 truncate">{data.subject}</p>}
            </div>
            <span className="shrink-0 text-xs text-gray-400 whitespace-nowrap mt-0.5">
                {formatDate(notification.created_at, 'datetime')}
            </span>
        </button>
    );
}
