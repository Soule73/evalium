import { useState, type FormEvent } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { breadcrumbs, trans } from '@/utils';
import { type Role, type GroupedPermissions } from '@/types/role';
import { PermissionSelector } from '@/Components';
import { Section, Badge, Button } from '@examena/ui';

interface Props {
    role: Role;
    groupedPermissions: GroupedPermissions;
}

export default function EditRole({ role, groupedPermissions }: Props) {
    const allPermissionIds = Object.values(groupedPermissions)
        .flat()
        .map(p => p.id);

    const [selectedPermissions, setSelectedPermissions] = useState<number[]>(
        role?.permissions?.map(p => p.id) ?? []
    );
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handlePermissionToggle = (permissionId: number) => {
        setSelectedPermissions(prev =>
            prev.includes(permissionId)
                ? prev.filter(id => id !== permissionId)
                : [...prev, permissionId]
        );
    };

    const selectAll = () => setSelectedPermissions(allPermissionIds);
    const deselectAll = () => setSelectedPermissions([]);

    const handleCancel = () => router.visit(route('admin.roles.index'));

    const handleSync = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post(
            route('admin.roles.sync-permissions', { role: role.id }),
            { permissions: selectedPermissions },
            {
                onFinish: () => setIsSubmitting(false),
                preserveScroll: true,
            }
        );
    };

    return (
        <AuthenticatedLayout
            title={trans('admin_pages.roles.configure_permissions')}
            breadcrumb={breadcrumbs.roleEdit(role.name)}
        >
            <form onSubmit={handleSync}>
                <Section
                    title={trans('admin_pages.roles.configure_title', { role: role.name })}
                    subtitle={trans('admin_pages.roles.configure_subtitle')}
                    actions={
                        <div className="flex gap-3">
                            <Button
                                type="button"
                                onClick={handleCancel}
                                color="secondary"
                                variant="outline"
                                disabled={isSubmitting}
                            >
                                {trans('common.cancel')}
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                disabled={isSubmitting}
                                data-e2e="permission-save-button"
                            >
                                {isSubmitting
                                    ? trans('admin_pages.roles.saving')
                                    : trans('admin_pages.roles.save_permissions')}
                            </Button>
                        </div>
                    }
                >
                    <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div className="flex items-center gap-2">
                            <Badge label={role.name} type="info" />
                            <span className="text-sm text-blue-800">
                                {trans('admin_pages.roles.system_role_notice')}
                            </span>
                        </div>
                    </div>

                    <PermissionSelector
                        groupedPermissions={groupedPermissions}
                        selectedPermissions={selectedPermissions}
                        onPermissionToggle={handlePermissionToggle}
                        onSelectAll={selectAll}
                        onDeselectAll={deselectAll}
                    />
                </Section>
            </form>
        </AuthenticatedLayout>
    );
}
