import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils';
import { Permission, Role, GroupedPermissions } from '@/types/role';
import { useRoleForm } from '@/hooks';
import { trans } from '@/utils';
import { RoleForm } from '@/Components';

interface Props {
    role: Role;
    allPermissions: Permission[];
    groupedPermissions: GroupedPermissions;
}

export default function EditRole({ role, allPermissions, groupedPermissions }: Props) {
    const isSystemRole = ['super_admin', 'admin', 'teacher', 'student'].includes(role.name);

    const {
        formData,
        errors,
        isSubmitting,
        handleFieldChange,
        handlePermissionToggle,
        selectAll,
        deselectAll,
        handleCancel,
        handleSubmit,
        handleSyncPermissions,
    } = useRoleForm({
        initialData: {
            name: role.name,
            permissions: role?.permissions?.map(p => p.id) ?? [],
        },
        allPermissions,
        onSuccessRoute: route('admin.roles.index'),
    });


    return (
        <AuthenticatedLayout title={trans('admin_pages.roles.edit')} breadcrumb={breadcrumbs.roleEdit(role.name)}>
            <RoleForm
                title={trans('admin_pages.roles.edit_title')}
                subtitle={trans('admin_pages.roles.edit_subtitle')}
                formData={formData}
                errors={errors}
                isSubmitting={isSubmitting}
                groupedPermissions={groupedPermissions}
                onSubmit={handleSubmit}
                onCancel={handleCancel}
                onFieldChange={handleFieldChange}
                onPermissionToggle={handlePermissionToggle}
                onSelectAll={selectAll}
                onDeselectAll={deselectAll}
                isSystemRole={isSystemRole}
                onSync={handleSyncPermissions}
                submitButtonText={trans('admin_pages.roles.update_button')}
                submittingText={trans('admin_pages.roles.updating')}
            />
        </AuthenticatedLayout>
    );
}
