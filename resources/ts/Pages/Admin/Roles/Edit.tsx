import { useMemo, useState, type FormEvent } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { type Role, type GroupedPermissions } from '@/types/role';
import { PermissionSelector } from '@/Components';
import { Section, Badge, Button } from '@evalium/ui';

interface Props {
    role: Role;
    groupedPermissions: GroupedPermissions;
}

export default function EditRole({ role, groupedPermissions }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const allPermissionIds = Object.values(groupedPermissions)
        .flat()
        .map((p) => p.id);

    const [selectedPermissions, setSelectedPermissions] = useState<number[]>(
        role?.permissions?.map((p) => p.id) ?? [],
    );
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handlePermissionToggle = (permissionId: number) => {
        setSelectedPermissions((prev) =>
            prev.includes(permissionId)
                ? prev.filter((id) => id !== permissionId)
                : [...prev, permissionId],
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
            },
        );
    };

    const translations = useMemo(
        () => ({
            configurePermissions: t('admin_pages.roles.configure_permissions'),
            configureSubtitle: t('admin_pages.roles.configure_subtitle'),
            cancel: t('common.cancel'),
            saving: t('admin_pages.roles.saving'),
            savePermissions: t('admin_pages.roles.save_permissions'),
            systemRoleNotice: t('admin_pages.roles.system_role_notice'),
        }),
        [t],
    );

    const configureTitleTranslation = useMemo(() => {
        return t('admin_pages.roles.configure_title', { role: role.name });
    }, [t, role.name]);
    return (
        <AuthenticatedLayout
            title={translations.configurePermissions}
            breadcrumb={breadcrumbs.roleEdit(role.name)}
        >
            <form onSubmit={handleSync}>
                <Section
                    title={configureTitleTranslation}
                    subtitle={translations.configureSubtitle}
                    actions={
                        <div className="flex gap-3">
                            <Button
                                type="button"
                                onClick={handleCancel}
                                color="secondary"
                                variant="outline"
                                disabled={isSubmitting}
                            >
                                {translations.cancel}
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                disabled={isSubmitting}
                                data-e2e="permission-save-button"
                            >
                                {isSubmitting ? translations.saving : translations.savePermissions}
                            </Button>
                        </div>
                    }
                >
                    <div className="mb-4 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                        <div className="flex items-center gap-2">
                            <Badge label={role.name} type="info" />
                            <span className="text-sm text-indigo-800">
                                {translations.systemRoleNotice}
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
