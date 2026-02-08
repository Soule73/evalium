import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils';
import { useState } from 'react';
import { hasPermission } from '@/utils';
import { PageProps, Role } from '@/types';
import { trans } from '@/utils';
import { Button, ConfirmationModal, Section } from '@/Components';
import { RoleList } from '@/Components/shared/lists';

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

    const [deleteModal, setDeleteModal] = useState<{
        isOpen: boolean;
        roleId: number | null;
        roleName: string;
    }>({
        isOpen: false,
        roleId: null,
        roleName: '',
    });

    const handleCreate = () => {
        router.visit(route('admin.roles.create'));
    };

    const handleEdit = (roleId: number) => {
        router.visit(route('admin.roles.edit', { role: roleId }));
    };

    const handleDelete = (roleId: number) => {
        if (!deleteModal.roleId) return;
        router.delete(route('admin.roles.destroy', { role: roleId }), {
            onFinish: () => setDeleteModal({ isOpen: false, roleId: null, roleName: '' }),
        });
    };

    return (
        <AuthenticatedLayout
            title={trans('admin_pages.roles.title')}
            breadcrumb={breadcrumbs.roles()}
        >
            <Section
                title={trans('admin_pages.roles.title')}
                subtitle={trans('admin_pages.roles.subtitle')}
                actions={
                    canCreateRoles && (
                        <Button
                            onClick={handleCreate}
                            color="primary"
                            size="sm"
                            data-e2e="role-create-button"
                        >
                            {trans('admin_pages.roles.create')}
                        </Button>
                    )
                }
            >
                <RoleList
                    data={roles}
                    permissions={{
                        canCreate: canCreateRoles,
                        canUpdate: canUpdateRoles,
                        canDelete: canDeleteRoles,
                    }}
                    onEdit={handleEdit}
                    onDelete={(roleId, roleName) =>
                        setDeleteModal({
                            isOpen: true,
                            roleId,
                            roleName,
                        })
                    }
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
                testIdModal={`role-delete-modal-${deleteModal.roleName.toLowerCase()}`}
                testIdConfirmButton={`role-delete-confirm-button-${deleteModal.roleName.toLowerCase()}`}
                testIdCancelButton={`role-delete-cancel-button-${deleteModal.roleName.toLowerCase()}`}
            />
        </AuthenticatedLayout>
    );
}
