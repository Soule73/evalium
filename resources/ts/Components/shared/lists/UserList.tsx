import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { User } from '@/types';
import { formatDate, getRoleColor, getRoleLabel, trans } from '@/utils';
import { Button } from '@/Components';
import { Toggle } from '@examena/ui';
import { TrashIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface UserListProps {
  data: PaginationType<User>;
  variant?: 'admin' | 'classmates';
  permissions?: {
    canUpdate?: boolean;
    canDelete?: boolean;
    canToggleStatus?: boolean;
  };
  roles?: string[];
  onDeleteClick?: (user: { id: number; name: string }) => void;
  onForceDeleteClick?: (user: { id: number; name: string }) => void;
}

/**
 * Unified UserList component for displaying users
 *
 * Supports two variants:
 * - admin: Full user management with roles, status, actions
 * - classmates: Simple list of classmates with name and email
 */
export function UserList({
  data,
  variant = 'admin',
  permissions = {},
  roles = [],
  onDeleteClick,
  onForceDeleteClick
}: UserListProps) {
  const canViewUser = (role: string) => {
    return role === 'teacher';
  };

  const handleViewUser = (userId: number) => {
    router.visit(route('admin.users.show.teacher', { user: userId }));
  };

  const handleToggleStatus = (userId: number) => {
    router.patch(route('admin.users.toggle-status', { user: userId }), {}, {
      preserveScroll: true,
    });
  };

  const handleRestoreUser = (userId: number) => {
    router.post(route('admin.users.restore', { id: userId }), {}, {
      preserveScroll: true,
    });
  };

  const config: EntityListConfig<User> = {
    entity: variant === 'classmates' ? 'classmate' : 'user',

    columns: [
      {
        key: 'name',
        labelKey: variant === 'classmates' ? 'student_enrollment_pages.classmates.student_name' : 'admin_pages.users.name',
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
                  <div className="text-sm font-medium text-gray-900">{user.name}</div>
                </div>
              </div>
            );
          }

          return (
            <div>
              <div className="flex items-center gap-2">
                <span className="text-sm font-medium text-gray-900">{user.name}</span>
                {user.deleted_at && (
                  <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                    {trans('admin_pages.users.deleted')}
                  </span>
                )}
              </div>
              <div className="text-sm text-gray-500">{user.email}</div>
            </div>
          );
        }
      },
      {
        key: 'email',
        labelKey: 'student_enrollment_pages.classmates.email',
        render: (user: User) => <span className="text-gray-700">{user.email || '-'}</span>,
        conditional: (v) => v === 'classmates',
      },
      {
        key: 'role',
        labelKey: 'admin_pages.users.role',
        render: (user: User) => (
          (user?.roles?.length ?? 0) > 0 ? (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRoleColor(user.roles?.[0]?.name ?? '')}`}>
              {getRoleLabel(user.roles?.[0]?.name ?? '')}
            </span>
          ) : null
        ),
        conditional: (v) => v === 'admin',
      },
      {
        key: 'status',
        labelKey: 'admin_pages.users.status',
        render: (user: User) => (
          <div className="flex items-center gap-2">
            {user.deleted_at ? (
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                {trans('admin_pages.users.deleted')}
              </span>
            ) : permissions.canToggleStatus ? (
              <Toggle
                checked={user.is_active}
                onChange={() => handleToggleStatus(user.id)}
                size="md"
                color="green"
                showLabel={true}
                activeLabel={trans('admin_pages.common.active')}
                inactiveLabel={trans('admin_pages.common.inactive')}
              />
            ) : (
              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                {user.is_active ? trans('admin_pages.common.active') : trans('admin_pages.common.inactive')}
              </span>
            )}
          </div>
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
      }
    ],

    actions: [],

    filters: variant === 'admin' ? [
      {
        key: 'role',
        type: 'select',
        labelKey: 'admin_pages.users.filter_role',
        options: [{ label: trans('admin_pages.users.all_roles'), value: '' }].concat(
          roles.map((role: string) => ({ label: getRoleLabel(role), value: role }))
        )
      },
      {
        key: 'status',
        type: 'select',
        labelKey: 'admin_pages.users.filter_status',
        options: [
          { label: trans('admin_pages.users.all_status'), value: '' },
          { label: trans('admin_pages.common.active'), value: 'active' },
          { label: trans('admin_pages.common.inactive'), value: 'inactive' }
        ]
      },
      {
        key: 'include_deleted',
        type: 'select',
        labelKey: 'admin_pages.common.search',
        options: [
          { label: trans('admin_pages.common.active'), value: '' },
          { label: trans('admin_pages.users.deleted'), value: '1' }
        ]
      }
    ] : undefined
  };

  if (variant === 'admin' && (permissions.canUpdate || permissions.canDelete)) {
    config.columns.push({
      key: 'actions',
      labelKey: 'admin_pages.common.actions',
      render: (user: User) => {
        const userRole = user.roles?.length && user.roles[0] ? user.roles[0].name : '';

        return (
          <div className="flex items-center gap-2">
            {user.deleted_at ? (
              <>
                <Button
                  onClick={() => handleRestoreUser(user.id)}
                  color="success"
                  size="sm"
                  variant='outline'
                  title={trans('admin_pages.users.restore')}
                >
                  <ArrowPathIcon className="h-4 w-4" />
                </Button>
                {permissions.canDelete && onForceDeleteClick && (
                  <Button
                    onClick={() => onForceDeleteClick({ id: user.id, name: user.name })}
                    color="danger"
                    size="sm"
                    variant='outline'
                    title={trans('admin_pages.users.force_delete')}
                  >
                    <TrashIcon className="h-4 w-4" />
                  </Button>
                )}
              </>
            ) : (
              <>
                {canViewUser(userRole) && permissions.canUpdate && (
                  <Button
                    onClick={() => handleViewUser(user.id)}
                    color="secondary"
                    size="sm"
                    variant='outline'
                  >
                    {trans('admin_pages.common.view')}
                  </Button>
                )}
                {permissions.canDelete && !canViewUser(userRole) && onDeleteClick && (
                  <Button
                    onClick={() => onDeleteClick({ id: user.id, name: user.name })}
                    color="danger"
                    size="sm"
                    variant='outline'
                    title={trans('admin_pages.common.delete')}
                  >
                    <TrashIcon className="h-4 w-4" />
                  </Button>
                )}
              </>
            )}
          </div>
        );
      },
      sortable: false
    });
  }

  return (
    <BaseEntityList
      data={data}
      config={config}
      variant={variant}
      searchPlaceholder={
        variant === 'classmates'
          ? trans('student_enrollment_pages.classmates.search_placeholder')
          : trans('admin_pages.users.search_placeholder')
      }
      emptyMessage={
        variant === 'classmates'
          ? trans('student_enrollment_pages.classmates.empty_subtitle')
          : trans('admin_pages.users.empty_subtitle')
      }
    />
  );
}
