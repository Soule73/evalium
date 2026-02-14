import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { route } from 'ziggy-js';
import { breadcrumbs, hasPermission, trans } from '@/utils';
import { type PageProps, type Role } from '@/types';
import { Section } from '@/Components';
import { RoleList } from '@/Components/shared/lists';

interface Props {
    roles: PaginationType<Role>;
    filters: {
        search: string;
        per_page: number;
    };
}

export default function RoleIndex({ roles }: Props) {
    const { auth } = usePage<PageProps>().props;
    const canUpdateRoles = hasPermission(auth.permissions, 'update roles');

    const handleEdit = (roleId: number) => {
        router.visit(route('admin.roles.edit', { role: roleId }));
    };

    return (
        <AuthenticatedLayout
            title={trans('admin_pages.roles.title')}
            breadcrumb={breadcrumbs.roles()}
        >
            <Section
                title={trans('admin_pages.roles.title')}
                subtitle={trans('admin_pages.roles.config_subtitle')}
            >
                <RoleList
                    data={roles}
                    permissions={{
                        canCreate: false,
                        canUpdate: canUpdateRoles,
                        canDelete: false,
                    }}
                    onEdit={handleEdit}
                />
            </Section>
        </AuthenticatedLayout>
    );
}