import { usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { UserGroupIcon, BookOpenIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { useState } from 'react';
import CreateUser from './Create';
import { User, PageProps } from '@/types';
import { breadcrumbs } from '@/utils';
import { hasPermission } from '@/utils';
import { trans } from '@/utils';
import { Stat, Section, ConfirmationModal, Button } from '@/Components';
import { useConfirmationModal } from '@/hooks';
import { PaginationType } from '@/types/datatable';
import { UserList } from '@/Components/shared/lists';
import { router } from '@inertiajs/react';


interface Props extends PageProps {
    users: PaginationType<User>;
    roles: string[];
    canManageAdmins: boolean;
}

export default function UserIndex({ users, roles }: Props) {
    const { auth } = usePage<PageProps>().props;

    const canCreateUsers = hasPermission(auth.permissions, 'create users');
    const canUpdateUsers = hasPermission(auth.permissions, 'update users');
    const canToggleUserStatus = hasPermission(auth.permissions, 'update users');
    const canDeleteUsers = hasPermission(auth.permissions, 'delete users');

    const [isShowCreateModal, setIsShowCreateModal] = useState(false);
    const deleteModal = useConfirmationModal<{ id: number; name: string }>();
    const forceDeleteModal = useConfirmationModal<{ id: number; name: string }>();

    const handleDeleteUser = (userId: number) => {
        if (!deleteModal.data) return;
        router.delete(route('admin.users.destroy', { user: userId }), {
            onFinish: () => deleteModal.closeModal()
        });
    };

    const handleForceDeleteUser = (userId: number) => {
        if (!forceDeleteModal.data) return;
        router.delete(route('admin.users.force-delete', { id: userId }), {
            onFinish: () => forceDeleteModal.closeModal()
        });
    };

    return (
        <AuthenticatedLayout title={trans('admin_pages.users.title')}
            breadcrumb={breadcrumbs.users()}
        >

            <CreateUser
                roles={roles}
                isOpen={isShowCreateModal}
                onClose={() => setIsShowCreateModal(false)}
            />
            <Stat.Group columns={3} className="mb-6">
                <Stat.Item
                    title={trans('admin_pages.users.all_users')}
                    value={users.total}
                    icon={UserGroupIcon}
                />
                <Stat.Item
                    title={trans('admin_pages.roles.role_labels.teacher')}
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'teacher')).length}
                    icon={BookOpenIcon}
                />
                <Stat.Item
                    title={trans('admin_pages.roles.role_labels.admin')}
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'admin')).length}
                    icon={ShieldCheckIcon}
                />
            </Stat.Group>
            <Section title={trans('admin_pages.users.subtitle')}
                actions={
                    canCreateUsers && (
                        <Button
                            onClick={() => setIsShowCreateModal(true)}
                            size="sm"
                        >
                            {trans('admin_pages.users.create')}
                        </Button>
                    )
                }
            >
                <UserList
                    data={users}
                    roles={roles}
                    permissions={{
                        canUpdate: canUpdateUsers,
                        canToggleStatus: canToggleUserStatus,
                        canDelete: canDeleteUsers
                    }}
                    onDeleteClick={(user) => deleteModal.openModal(user)}
                    onForceDeleteClick={(user) => forceDeleteModal.openModal(user)}
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