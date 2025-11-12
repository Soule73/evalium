import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { route } from 'ziggy-js';
import { ShieldCheckIcon } from '@heroicons/react/24/outline';
import { breadcrumbs, getRoleLabel } from '@/utils';
import { useState } from 'react';
import { hasPermission } from '@/utils';
import { PageProps, Role } from '@/types';
import { trans } from '@/utils';
import { Badge, Button, ConfirmationModal, DataTable, Section } from '@/Components';

interface Props {
    roles: PaginationType<Role>;
    allPermissions: Array<{ id: number; name: string }>;
    filters: {
        search: string;
        per_page: number;
    };
}

export default function RoleIndex({ roles }: Props) {
    const { auth } = usePage<PageProps>().props;

    const canCreateRoles = hasPermission(auth.permissions, 'create roles');
    const canUpdateRoles = hasPermission(auth.permissions, 'update roles');
    const canDeleteRoles = hasPermission(auth.permissions, 'delete roles');

    const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; roleId: number | null; roleName: string }>({
        isOpen: false,
        roleId: null,
        roleName: ''
    });

    const handleCreate = () => {
        router.visit(route('roles.create'));
    };

    const handleEdit = (roleId: number) => {
        router.visit(route('roles.edit', { role: roleId }));
    };

    const handleDelete = (roleId: number) => {
        if (!deleteModal.roleId) return;
        router.delete(route('roles.destroy', { role: roleId }), {
            onFinish: () => setDeleteModal({ isOpen: false, roleId: null, roleName: '' })
        });
    };

    const isSystemRole = (roleName: string) => {
        return ['super_admin', 'admin', 'teacher', 'student'].includes(roleName);
    };

    const dataTableConfig: DataTableConfig<Role> = {
        searchPlaceholder: trans('admin_pages.roles.search_placeholder'),
        perPageOptions: [10, 15, 25, 50],
        emptyState: {
            title: trans('admin_pages.roles.empty_title'),
            subtitle: trans('admin_pages.roles.empty_subtitle'),
            icon: <ShieldCheckIcon className="w-12 h-12 text-gray-400" />,
            actions: canCreateRoles ? (
                <Button onClick={handleCreate} color="primary">
                    {trans('admin_pages.roles.create_button')}
                </Button>
            ) : undefined,
        },
        emptySearchState: {
            title: trans('admin_pages.common.no_results'),
            subtitle: trans('admin_pages.common.no_results_subtitle'),
            resetLabel: trans('admin_pages.common.clear_search'),
        },
        columns: [
            {
                key: 'name',
                label: trans('admin_pages.roles.name'),
                render: (role) => (
                    <div className="flex items-center gap-2">
                        <ShieldCheckIcon className="w-5 h-5 text-blue-600" />
                        <div>
                            <div className="text-sm font-medium text-gray-900">{getRoleLabel(role.name)}</div>
                            <div className="text-xs text-gray-500">{role.name}</div>
                        </div>
                        {isSystemRole(role.name) && (
                            <Badge label={trans('admin_pages.roles.system_role')} type="info" />
                        )}
                    </div>
                ),
                sortable: true,
            },
            {
                key: 'permissions',
                label: trans('admin_pages.roles.permissions'),
                render: (role) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{trans('admin_pages.roles.permissions_count', { count: role.permissions_count || 0 })}</div>
                        <div className="text-xs text-gray-500 truncate max-w-md">
                            {role.permissions && role.permissions.length > 0 ? (
                                <>
                                    {role.permissions.slice(0, 3).map((p) => p.name).join(', ')}
                                    {role.permissions.length > 3 && ` +${role.permissions.length - 3}`}
                                </>
                            ) : (
                                trans('admin_pages.roles.no_permissions')
                            )}
                        </div>
                    </div>
                ),
            },
            {
                key: 'actions',
                label: trans('admin_pages.common.actions'),
                render: (role) => (canUpdateRoles || canDeleteRoles) ? (
                    <div className="flex gap-2">
                        {canUpdateRoles && (
                            <Button
                                onClick={() => handleEdit(role.id)}
                                size="sm"
                                color="primary"
                                variant="outline"
                            >
                                {isSystemRole(role.name) ? trans('admin_pages.common.view') : trans('admin_pages.common.edit')}
                            </Button>
                        )}
                        {!isSystemRole(role.name) && canDeleteRoles && (
                            <Button
                                onClick={() => setDeleteModal({
                                    isOpen: true,
                                    roleId: role.id,
                                    roleName: getRoleLabel(role.name)
                                })}
                                size="sm"
                                color="danger"
                            >
                                {trans('admin_pages.common.delete')}
                            </Button>
                        )}
                    </div>
                ) : null,
            },
        ],
    };

    return (
        <AuthenticatedLayout title={trans('admin_pages.roles.title')}
            breadcrumb={breadcrumbs.roles()}
        >
            <Section
                title={trans('admin_pages.roles.title')}
                subtitle={trans('admin_pages.roles.subtitle')}
                actions={canCreateRoles && (
                    <Button onClick={handleCreate} color="primary" size='sm'>
                        {trans('admin_pages.roles.create')}
                    </Button>
                )}
            >
                <DataTable
                    config={dataTableConfig}
                    data={roles}
                    onStateChange={(state) => {
                        router.get(route('roles.index'),
                            {
                                search: state.search,
                                per_page: state.perPage,
                                page: state.page
                            },
                            { preserveState: true, preserveScroll: true }
                        );
                    }}
                />
            </Section>

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, roleId: null, roleName: '' })}
                onConfirm={() => deleteModal.roleId && handleDelete(deleteModal.roleId)}
                title={trans('admin_pages.roles.delete_title')}
                message={trans('admin_pages.roles.delete_message', { name: deleteModal.roleName })}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
