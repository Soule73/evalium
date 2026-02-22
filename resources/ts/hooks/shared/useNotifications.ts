import { useCallback, useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import type { AppNotification } from '@/types';
import { route } from 'ziggy-js';

interface NotificationState {
    notifications: AppNotification[];
    unreadCount: number;
    hasMore: boolean;
    loading: boolean;
}

interface UseNotificationsReturn extends NotificationState {
    fetchNotifications: () => Promise<void>;
    markRead: (id: string) => void;
    markAllRead: () => Promise<void>;
    deleteNotification: (id: string) => void;
    deleteAll: () => void;
}

/**
 * Hook to manage user notifications: fetch, mark as read, and polling.
 *
 * Polls the backend every 60 seconds when the tab is visible to refresh the unread count.
 */
export function useNotifications(): UseNotificationsReturn {
    const [state, setState] = useState<NotificationState>({
        notifications: [],
        unreadCount: 0,
        hasMore: false,
        loading: false,
    });

    const fetchNotifications = useCallback(async () => {
        setState((prev) => ({ ...prev, loading: true }));

        try {
            const response = await fetch(route('notifications.index'), {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            setState({
                notifications: data.notifications ?? [],
                unreadCount: data.unread_count ?? 0,
                hasMore: data.has_more ?? false,
                loading: false,
            });
        } catch {
            setState((prev) => ({ ...prev, loading: false }));
        }
    }, []);

    const markRead = useCallback((id: string) => {
        setState((prev) => ({
            ...prev,
            notifications: prev.notifications.map((n) =>
                n.id === id ? { ...n, read_at: new Date().toISOString() } : n,
            ),
            unreadCount: Math.max(0, prev.unreadCount - 1),
        }));

        fetch(route('notifications.read', { id }), {
            method: 'POST',
            keepalive: true,
            headers: {
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                        ?.content ?? '',
                Accept: 'application/json',
            },
        });
    }, []);

    const markAllRead = useCallback(async () => {
        await fetch(route('notifications.read-all'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                        ?.content ?? '',
                Accept: 'application/json',
            },
        });

        setState((prev) => ({
            ...prev,
            notifications: prev.notifications.map((n) => ({
                ...n,
                read_at: n.read_at ?? new Date().toISOString(),
            })),
            unreadCount: 0,
        }));
    }, []);

    const deleteNotification = useCallback((id: string) => {
        setState((prev) => {
            const target = prev.notifications.find((n) => n.id === id);
            const wasUnread = target?.read_at === null;

            return {
                ...prev,
                notifications: prev.notifications.filter((n) => n.id !== id),
                unreadCount: wasUnread ? Math.max(0, prev.unreadCount - 1) : prev.unreadCount,
            };
        });

        fetch(route('notifications.delete', { id }), {
            method: 'DELETE',
            keepalive: true,
            headers: {
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                        ?.content ?? '',
                Accept: 'application/json',
            },
        });
    }, []);

    const deleteAll = useCallback(() => {
        setState((prev) => ({ ...prev, notifications: [], unreadCount: 0 }));

        fetch(route('notifications.delete-all'), {
            method: 'DELETE',
            keepalive: true,
            headers: {
                'X-CSRF-TOKEN':
                    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
                        ?.content ?? '',
                Accept: 'application/json',
            },
        });
    }, []);

    useEffect(() => {
        const POLL_INTERVAL_MS = 60_000;

        const reload = () => {
            if (document.visibilityState === 'visible') {
                router.reload({ only: ['notifications'] });
            }
        };

        const interval = setInterval(reload, POLL_INTERVAL_MS);
        document.addEventListener('visibilitychange', reload);

        return () => {
            clearInterval(interval);
            document.removeEventListener('visibilitychange', reload);
        };
    }, []);

    return { ...state, fetchNotifications, markRead, markAllRead, deleteNotification, deleteAll };
}
