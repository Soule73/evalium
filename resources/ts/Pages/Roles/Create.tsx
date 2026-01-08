import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils';
import { Permission, GroupedPermissions } from '@/types/role';
import { useRoleForm } from '@/hooks';
import { trans } from '@/utils';
import { RoleForm } from '@/Components';

interface Props {
    permissions: Permission[];
    groupedPermissions: GroupedPermissions;
}

export default function CreateRole({ permissions, groupedPermissions }: Props) {
    const {
        formData,
        errors,
        isSubmitting,
        handleFieldChange,
        handlePermissionToggle,
        selectAll,
        deselectAll,
        handleCancel,
        handleSubmit
    } = useRoleForm({
        allPermissions: permissions,
        onSuccessRoute: route('roles.index'),
    });



    return (
        <AuthenticatedLayout title={trans('admin_pages.roles.create')} breadcrumb={breadcrumbs.roleCreate()}>
            <RoleForm
                title={trans('admin_pages.roles.create_title')}
                subtitle={trans('admin_pages.roles.create_subtitle')}
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
                submitButtonText={trans('admin_pages.roles.create_button')}
                submittingText={trans('admin_pages.roles.creating')}
            />
        </AuthenticatedLayout>
    );
}

