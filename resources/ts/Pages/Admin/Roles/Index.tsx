import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { route } from 'ziggy-js';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { type PageProps, type Role } from '@/types';
import { Section } from '@/Components';
import { RoleList } from '@/Components/shared/lists';

interface Props extends PageProps {
    roles: PaginationType<Role>;
    filters: {
        search: string;
        per_page: number;
    };
}

export default function RoleIndex({ auth, roles }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canUpdateRoles = hasPermission(auth.permissions, 'update roles');

    const translations = useMemo(
        () => ({
            title: t('admin_pages.roles.title'),
            configSubtitle: t('admin_pages.roles.config_subtitle'),
        }),
        [t],
    );

    const handleEdit = (roleId: number) => {
        router.visit(route('admin.roles.edit', { role: roleId }));
    };

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.roles()}>
            <Section
                variant="flat"
                title={translations.title}
                subtitle={translations.configSubtitle}
            >
                <RoleList
                    data={roles}
                    permissions={{
                        canCreate: false,
                        canUpdate: canUpdateRoles,
                    }}
                    onEdit={handleEdit}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
