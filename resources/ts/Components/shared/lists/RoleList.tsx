import { useMemo } from 'react';
import { Badge, Button } from '@/Components';
import { BaseEntityList } from './BaseEntityList';
import { type EntityListConfig } from './types/listConfig';
import { type Role } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { useTranslations } from '@/hooks';
import { useFormatters } from '@/hooks/shared/useFormatters';
import { ShieldCheckIcon } from '@heroicons/react/24/outline';

interface RoleListProps {
    data: PaginationType<Role>;
    permissions?: {
        canCreate?: boolean;
        canUpdate?: boolean;
        canDelete?: boolean;
    };
    onEdit?: (roleId: number) => void;
    onDelete?: (roleId: number, roleName: string) => void;
}

export function RoleList({ data, permissions = {}, onEdit, onDelete }: RoleListProps) {
    const { t } = useTranslations();
    const { getRoleLabel } = useFormatters();

    const config: EntityListConfig<Role> = useMemo(() => {
        const isSystemRole = (roleName: string) => {
            return ['super_admin', 'admin', 'teacher', 'student'].includes(roleName);
        };

        return {
            entity: 'role',
            columns: [
                {
                    key: 'name',
                    labelKey: 'admin_pages.roles.name',
                    render: (role) => (
                        <div className="flex items-center gap-2">
                            <ShieldCheckIcon className="w-5 h-5 text-indigo-600" />
                            <p
                                data-e2e={`role-name-${role.name.toLowerCase()}`}
                                className="text-sm font-medium text-gray-900"
                            >
                                {getRoleLabel(role.name)}
                            </p>
                            {isSystemRole(role.name) && (
                                <Badge label={t('admin_pages.roles.system_role')} type="info" />
                            )}
                        </div>
                    ),
                    sortable: true,
                },
                {
                    key: 'permissions',
                    labelKey: 'admin_pages.roles.permissions',
                    render: (role) => (
                        <div>
                            <div className="text-sm font-medium text-gray-900">
                                {t('admin_pages.roles.permissions_count', {
                                    count: role.permissions_count || 0,
                                })}
                            </div>
                            <div className="text-xs text-gray-500 truncate max-w-md">
                                {role.permissions && role.permissions.length > 0 ? (
                                    <span data-e2e={`role-permissions-${role.name.toLowerCase()}`}>
                                        {role.permissions
                                            .slice(0, 3)
                                            .map((p) => p.name)
                                            .join(', ')}
                                        {role.permissions.length > 3 &&
                                            ` +${role.permissions.length - 3}`}
                                    </span>
                                ) : (
                                    <span
                                        data-e2e={`role-no-permissions-${role.name.toLowerCase()}`}
                                    >
                                        {t('admin_pages.roles.no_permissions')}
                                    </span>
                                )}
                            </div>
                        </div>
                    ),
                },
                {
                    key: 'actions',
                    labelKey: 'admin_pages.common.actions',
                    render: (role) => {
                        const roleLabel = getRoleLabel(role.name);
                        return permissions.canUpdate || permissions.canDelete ? (
                            <div className="flex gap-2">
                                {permissions.canUpdate && onEdit && (
                                    <Button
                                        onClick={() => onEdit(role.id)}
                                        size="sm"
                                        color="primary"
                                        variant="outline"
                                        data-e2e={`role-edit-${role.name.toLowerCase()}`}
                                    >
                                        {isSystemRole(role.name)
                                            ? t('admin_pages.common.view')
                                            : t('admin_pages.common.edit')}
                                    </Button>
                                )}
                                {!isSystemRole(role.name) && permissions.canDelete && onDelete && (
                                    <Button
                                        onClick={() => onDelete(role.id, roleLabel)}
                                        size="sm"
                                        color="danger"
                                        data-e2e={`role-delete-${role.name.toLowerCase()}`}
                                    >
                                        {t('admin_pages.common.delete')}
                                    </Button>
                                )}
                            </div>
                        ) : null;
                    },
                },
            ],
        };
    }, [permissions.canUpdate, permissions.canDelete, onEdit, onDelete, getRoleLabel, t]);

    return <BaseEntityList data={data} config={config} variant="admin" />;
}
