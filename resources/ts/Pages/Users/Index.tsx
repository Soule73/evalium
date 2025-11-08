import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { formatDate, getRoleColor, getRoleLabel } from '@/utils';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { UserGroupIcon, TrashIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { useState } from 'react';
import CreateUser from './Create';
import { User, PageProps, Group } from '@/types';
import { breadcrumbs } from '@/utils';
import { hasPermission } from '@/utils';
import { trans } from '@/utils';
import { Toggle, Button, StatCard, Section, DataTable, ConfirmationModal } from '@/Components';
import { useConfirmationModal } from '@/hooks';


interface Props extends PageProps {
    users: PaginationType<User>;
    roles: string[];
    groups: Group[];
    canManageAdmins: boolean;
}

export default function UserIndex({ users, roles, groups }: Props) {
    const { auth } = usePage<PageProps>().props;

    const canCreateUsers = hasPermission(auth.permissions, 'create users');
    const canUpdateUsers = hasPermission(auth.permissions, 'update users');
    const canToggleUserStatus = hasPermission(auth.permissions, 'update users');
    const canDeleteUsers = hasPermission(auth.permissions, 'delete users');

    const [isShowCreateModal, setIsShowCreateModal] = useState(false);
    const deleteModal = useConfirmationModal<{ id: number; name: string }>();
    const forceDeleteModal = useConfirmationModal<{ id: number; name: string }>();

    const handleCreateUser = () => {
        setIsShowCreateModal(true);
    };

    const handleViewUser = (userId: number, role: string) => {
        if (role === 'student') {
            router.visit(route('users.show.student', { user: userId }));
        } else if (role === 'teacher') {
            router.visit(route('users.show.teacher', { user: userId }));
        }

    };

    const canViewUser = (role: string) => {
        return ['student', 'teacher'].includes(role);
    }

    const handleToggleStatus = (userId: number) => {
        router.patch(route('users.toggle-status', { user: userId }), {}, {
            preserveScroll: true,
        });
    };

    const handleDeleteUser = (userId: number) => {
        if (!deleteModal.data) return;
        router.delete(route('users.destroy', { user: userId }), {
            onFinish: () => deleteModal.closeModal()
        });
    };

    const handleRestoreUser = (userId: number) => {
        router.post(route('users.restore', { id: userId }), {}, {
            preserveScroll: true,
        });
    };

    const handleForceDeleteUser = (userId: number) => {
        if (!forceDeleteModal.data) return;
        router.delete(route('users.force-delete', { id: userId }), {
            onFinish: () => forceDeleteModal.closeModal()
        });
    };


    const dataTableConfig: DataTableConfig<User> = {
        columns: [
            {
                key: 'name',
                label: trans('admin_pages.users.name'),
                render: (user) => (
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
                )
            },
            {
                key: 'role',
                label: trans('admin_pages.users.role'),
                render: (user) => (
                    (user?.roles?.length ?? 0) > 0 ? (
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRoleColor(user.roles?.[0]?.name ?? '')}`}>
                            {getRoleLabel(user.roles?.[0]?.name ?? '')}
                        </span>
                    ) : null
                )
            },
            {
                key: 'status',
                label: trans('admin_pages.users.status'),
                render: (user) => (
                    <div className="flex items-center gap-2">
                        {user.deleted_at ? (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                {trans('admin_pages.users.deleted')}
                            </span>
                        ) : canToggleUserStatus ? (
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
                )
            },
            {
                key: 'created_at',
                label: trans('admin_pages.users.created_at'),
                render: (user) => (
                    <span className="text-sm text-gray-500">{formatDate(user.created_at)}</span>
                )
            },
            {
                key: 'actions',
                label: trans('admin_pages.common.actions'),
                render: (user) => (
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
                                {canDeleteUsers && (
                                    <Button
                                        onClick={() => forceDeleteModal.openModal({ id: user.id, name: user.name })}
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
                                {canViewUser(user.roles?.length && user.roles[0] ? user.roles[0].name : ''

                                ) && canUpdateUsers && (
                                        <Button
                                            onClick={() => handleViewUser(user.id, user.roles?.length && user.roles[0] ? user.roles[0].name : '')}
                                            color="secondary"
                                            size="sm"
                                            variant='outline'
                                        >
                                            {trans('admin_pages.common.view')}
                                        </Button>
                                    )}
                                {canDeleteUsers && !canViewUser(user.roles?.length && user.roles[0] ? user.roles[0].name : '') && (
                                    <Button
                                        onClick={() => deleteModal.openModal({ id: user.id, name: user.name })}
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
                )
            }
        ],
        searchPlaceholder: trans('admin_pages.users.search_placeholder'),
        filters: [
            {
                key: 'role',
                type: 'select',
                label: trans('admin_pages.users.filter_role'),
                options: [{ label: trans('admin_pages.users.all_roles'), value: '' }].concat(roles.map(role => ({ label: getRoleLabel(role), value: role })))
            },
            {
                key: 'status',
                type: 'select',
                label: trans('admin_pages.users.filter_status'),
                options: [
                    { label: trans('admin_pages.users.all_status'), value: '' },
                    { label: trans('admin_pages.common.active'), value: 'active' },
                    { label: trans('admin_pages.common.inactive'), value: 'inactive' }
                ]
            },
            {
                key: 'include_deleted',
                type: 'select',
                label: trans('admin_pages.common.search'),
                options: [
                    { label: trans('admin_pages.common.active'), value: '' },
                    { label: trans('admin_pages.users.deleted'), value: '1' }
                ]
            }
        ],
        emptyState: {
            title: trans('admin_pages.users.empty_title'),
            subtitle: trans('admin_pages.users.empty_subtitle'),
            icon: 'UserIcon'
        },
        emptySearchState: {
            title: trans('admin_pages.users.empty_title'),
            subtitle: trans('admin_pages.users.empty_subtitle'),
            resetLabel: trans('admin_pages.common.cancel')
        },
        perPageOptions: [10, 25, 50]
    };

    return (
        <AuthenticatedLayout title={trans('admin_pages.users.title')}
            breadcrumb={breadcrumbs.users()}
        >

            <CreateUser
                roles={roles}
                groups={groups}
                isOpen={isShowCreateModal}
                onClose={() => setIsShowCreateModal(false)}
            />
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title={trans('admin_pages.users.title')}
                    value={users.total}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <StatCard
                    title={trans('admin_pages.roles.role_labels.student')}
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'student')).length}
                    icon={UserGroupIcon}
                    color="green"
                />
                <StatCard
                    title={trans('admin_pages.roles.role_labels.teacher')}
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'teacher')).length}
                    icon={UserGroupIcon}
                    color="purple"
                />

                <StatCard
                    title={trans('admin_pages.roles.role_labels.admin')}
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'admin')).length}
                    icon={UserGroupIcon}
                    color="red"
                />
            </div>
            <Section title={trans('admin_pages.users.subtitle')}
                actions={canCreateUsers && (
                    <Button onClick={handleCreateUser} color="secondary" variant='outline' size='sm'>
                        {trans('admin_pages.users.create')}
                    </Button>
                )}
            >
                <DataTable
                    data={users}
                    config={dataTableConfig}
                />
            </Section>

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={deleteModal.closeModal}
                onConfirm={() => deleteModal.data && handleDeleteUser(deleteModal.data.id)}
                title={trans('admin_pages.users.delete_title')}
                message={trans('admin_pages.users.delete_message', { name: deleteModal.data?.name || '' })}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />

            <ConfirmationModal
                isOpen={forceDeleteModal.isOpen}
                onClose={forceDeleteModal.closeModal}
                onConfirm={() => forceDeleteModal.data && handleForceDeleteUser(forceDeleteModal.data.id)}
                title={trans('admin_pages.users.force_delete_title')}
                message={trans('admin_pages.users.force_delete_message', { name: forceDeleteModal.data?.name || '' })}
                confirmText={trans('admin_pages.users.force_delete')}
                cancelText={trans('admin_pages.common.cancel')}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}