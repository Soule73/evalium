import { useMemo } from 'react';
import { Button } from '@/Components';
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
    };
    onEdit?: (roleId: number) => void;
    onDelete?: (roleId: number, roleName: string) => void;
}

export function RoleList({ data, permissions = {}, onEdit }: RoleListProps) {
    const { t } = useTranslations();
    const { getRoleLabel } = useFormatters();

    const config: EntityListConfig<Role> = useMemo(() => {
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
                    labelKey: 'commons/table.actions',
                    render: (role) => {
                        return permissions.canUpdate ? (
                            <div className="flex gap-2">
                                {permissions.canUpdate && onEdit && (
                                    <Button
                                        onClick={() => onEdit(role.id)}
                                        size="sm"
                                        color="primary"
                                        variant="outline"
                                        data-e2e={`role-edit-${role.name.toLowerCase()}`}
                                    >
                                        {role.is_editable
                                            ? t('commons/ui.edit')
                                            : t('commons/ui.view')}
                                    </Button>
                                )}
                            </div>
                        ) : null;
                    },
                },
            ],
        };
    }, [permissions.canUpdate, onEdit, getRoleLabel, t]);

    return <BaseEntityList data={data} config={config} variant="admin" />;
}
