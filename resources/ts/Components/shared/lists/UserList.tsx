import { useMemo, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type User } from '@/types';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks';
import { Button } from '@/Components';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface UserListProps {
    data: PaginationType<User>;
    variant?: 'admin' | 'classmates';
    permissions?: {
        canUpdate?: boolean;
    };
    onView?: (userId: number) => void;
}

/**
 * Unified UserList component for displaying users.
 *
 * Supports two variants:
 * - admin: User management list with status badge and view action
 * - classmates: Simple list of classmates with name and email
 */
export function UserList({ data, variant = 'admin', permissions = {}, onView }: UserListProps) {
    const { t } = useTranslations();

    const handleViewUser = useCallback(
        (userId: number) => {
            if (onView) {
                onView(userId);
                return;
            }
            router.visit(route('admin.users.show', { user: userId }));
        },
        [onView],
    );

    const handleRestoreUser = useCallback((userId: number) => {
        router.post(route('admin.users.restore', { id: userId }), {}, { preserveScroll: true });
    }, []);

    const config: EntityListConfig<User> = useMemo(() => {
        const columns: EntityListConfig<User>['columns'] = [
            {
                key: 'name',
                labelKey:
                    variant === 'classmates'
                        ? 'student_enrollment_pages.classmates.student_name'
                        : 'admin_pages.users.name',
                render: (user: User, currentVariant) => {
                    if (currentVariant === 'classmates') {
                        return (
                            <div className="flex items-center">
                                <div className="shrink-0 h-10 w-10">
                                    <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span className="text-gray-600 font-medium text-sm">
                                            {user.name
                                                .split(' ')
                                                .map((n: string) => n[0])
                                                .join('')
                                                .toUpperCase()
                                                .slice(0, 2)}
                                        </span>
                                    </div>
                                </div>
                                <div className="ml-4">
                                    <div className="text-sm font-medium text-gray-900">
                                        {user.name}
                                    </div>
                                </div>
                            </div>
                        );
                    }

                    return (
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-sm font-medium text-gray-900">
                                    {user.name}
                                </span>
                                {user.deleted_at && (
                                    <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        {t('admin_pages.users.deleted')}
                                    </span>
                                )}
                            </div>
                            <div className="text-sm text-gray-500">{user.email}</div>
                        </div>
                    );
                },
            },
            {
                key: 'email',
                labelKey: 'student_enrollment_pages.classmates.email',
                render: (user: User) => <span className="text-gray-700">{user.email || '-'}</span>,
                conditional: (v) => v === 'classmates',
            },
            {
                key: 'status',
                labelKey: 'admin_pages.users.status',
                render: (user: User) => (
                    <span
                        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            user.deleted_at
                                ? 'bg-red-100 text-red-800'
                                : user.is_active
                                  ? 'bg-green-100 text-green-800'
                                  : 'bg-gray-100 text-gray-800'
                        }`}
                    >
                        {user.deleted_at
                            ? t('admin_pages.users.deleted')
                            : user.is_active
                              ? t('admin_pages.common.active')
                              : t('admin_pages.common.inactive')}
                    </span>
                ),
                conditional: (v) => v === 'admin',
            },
            {
                key: 'created_at',
                labelKey: 'admin_pages.users.created_at',
                render: (user: User) => (
                    <span className="text-sm text-gray-500">{formatDate(user.created_at)}</span>
                ),
                conditional: (v) => v === 'admin',
            },
        ];

        if (variant === 'admin' && permissions.canUpdate) {
            columns.push({
                key: 'actions',
                labelKey: 'admin_pages.common.actions',
                render: (user: User) => (
                    <div className="flex items-center gap-2">
                        {user.deleted_at ? (
                            <Button
                                onClick={() => handleRestoreUser(user.id)}
                                color="success"
                                size="sm"
                                variant="outline"
                                title={t('admin_pages.users.restore')}
                            >
                                <ArrowPathIcon className="h-4 w-4" />
                            </Button>
                        ) : (
                            <Button
                                onClick={() => handleViewUser(user.id)}
                                color="secondary"
                                size="sm"
                                variant="outline"
                            >
                                {t('admin_pages.common.view')}
                            </Button>
                        )}
                    </div>
                ),
                sortable: false,
            });
        }

        return {
            entity: variant === 'classmates' ? 'classmate' : 'user',
            columns,
            actions: [],
            filters:
                variant === 'admin'
                    ? [
                          {
                              key: 'status',
                              type: 'select' as const,
                              labelKey: 'admin_pages.users.filter_status',
                              options: [
                                  { label: t('admin_pages.users.all_status'), value: '' },
                                  { label: t('admin_pages.common.active'), value: 'active' },
                                  { label: t('admin_pages.common.inactive'), value: 'inactive' },
                              ],
                          },
                          {
                              key: 'include_deleted',
                              type: 'boolean' as const,
                              labelKey: 'admin_pages.users.include_deleted',
                              trueValue: '1',
                          },
                      ]
                    : undefined,
        };
    }, [variant, permissions.canUpdate, handleViewUser, handleRestoreUser, t]);

    return (
        <BaseEntityList
            data={data}
            config={config}
            variant={variant}
            searchPlaceholder={
                variant === 'classmates'
                    ? t('student_enrollment_pages.classmates.search_placeholder')
                    : t('admin_pages.users.search_placeholder')
            }
            emptyMessage={
                variant === 'classmates'
                    ? t('student_enrollment_pages.classmates.empty_subtitle')
                    : t('admin_pages.users.empty_subtitle')
            }
        />
    );
}
