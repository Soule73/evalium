import { FormEvent } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import { Button } from '@/Components/Button';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { Permission, Role, GroupedPermissions } from '@/types/role';
import { useRoleForm } from '@/hooks/useRoleForm';
import RoleForm from '@/Components/Roles/RoleForm';

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
        <AuthenticatedLayout title="Modifier un rôle" breadcrumb={breadcrumbs.roleEdit(role.name)}>
            <Section
                title="Modifier le rôle"
                subtitle={`Modification du rôle : ${role.name}`}
                actions={
                    <Button
                        type="button"
                        onClick={handleCancel}
                        variant='outline'
                        color="secondary"
                    >
                        Retour
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
                    submitButtonText="Enregistrer les modifications"
                    submittingText="Enregistrement..."
                />
            </Section>
        </AuthenticatedLayout>
    );
}
