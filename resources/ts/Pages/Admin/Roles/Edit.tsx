import { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { Permission, Role, GroupedPermissions } from '@/types/role';
import { useRoleForm } from '@/hooks';
import { trans } from '@/utils/translations';
import { Button, RoleForm, Section } from '@/Components';

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
        setIsSubmitting,
        handleFieldChange,
        handlePermissionToggle,
        selectAll,
        deselectAll,
        handleCancel,
        handleError,
        handleSuccess,
    } = useRoleForm({
        initialData: {
            name: role.name,
            permissions: role.permissions.map(p => p.id),
        },
        allPermissions,
        onSuccessRoute: route('roles.index'),
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.put(route('roles.update', { role: role.id }), formData as any, {
            onError: handleError,
            onSuccess: handleSuccess,
        });
    };

    const handleSyncPermissions = () => {
        setIsSubmitting(true);

        router.post(
            route('roles.sync-permissions', { role: role.id }),
            { permissions: formData.permissions } as any,
            {
                onError: handleError,
                onSuccess: handleSuccess,
                preserveScroll: true,
            }
        );
    };

    return (
        <AuthenticatedLayout title={trans('admin_pages.roles.edit')} breadcrumb={breadcrumbs.roleEdit(role.name)}>
            <Section
                title={trans('admin_pages.roles.edit_title')}
                subtitle={trans('admin_pages.roles.edit_subtitle')}
                actions={
                    <Button
                        type="button"
                        onClick={handleCancel}
                        variant='outline'
                        color="secondary"
                    >
                        {trans('admin_pages.common.back')}
                    </Button>
                }
            >
                <RoleForm
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
            </Section>
        </AuthenticatedLayout>
    );
}
