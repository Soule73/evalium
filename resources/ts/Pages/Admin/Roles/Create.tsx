import { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import { Button } from '@/Components/Button';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { Permission, GroupedPermissions } from '@/types/role';
import { useRoleForm } from '@/hooks/useRoleForm';
import RoleForm from '@/Components/Roles/RoleForm';
import { trans } from '@/utils/translations';

interface Props {
    permissions: Permission[];
    groupedPermissions: GroupedPermissions;
}

export default function CreateRole({ permissions, groupedPermissions }: Props) {
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
        allPermissions: permissions,
        onSuccessRoute: route('roles.index'),
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(route('roles.store'), formData as any, {
            onError: handleError,
            onSuccess: handleSuccess,
        });
    };

    return (
        <AuthenticatedLayout title={trans('admin_pages.roles.create')} breadcrumb={breadcrumbs.roleCreate()}>
            <Section
                title={trans('admin_pages.roles.create_title')}
                subtitle={trans('admin_pages.roles.create_subtitle')}
                actions={
                    <Button
                        type="button"
                        onClick={handleCancel}
                        color="secondary"
                        variant='outline'
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
                    submitButtonText={trans('admin_pages.roles.create_button')}
                    submittingText={trans('admin_pages.roles.creating')}
                />
            </Section>
        </AuthenticatedLayout>
    );
}

