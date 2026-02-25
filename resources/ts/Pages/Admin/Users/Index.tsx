import { usePage } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { UserGroupIcon, ShieldCheckIcon, StarIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { useMemo, useState } from 'react';
import { type User, type PageProps } from '@/types';
import { hasPermission } from '@/utils';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Stat, Section, Button, CreateUserModal } from '@/Components';
import { type PaginationType } from '@/types/datatable';
import { UserList } from '@/Components/shared/lists';

interface Props extends PageProps {
    users: PaginationType<User>;
    roles: string[];
    canManageAdmins: boolean;
    adminCount: number;
    superAdminCount: number;
}

export default function UserIndex({ users, roles, adminCount, superAdminCount }: Props) {
    const { auth } = usePage<PageProps>().props;
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const canCreateUsers = hasPermission(auth.permissions, 'create users');
    const canUpdateUsers = hasPermission(auth.permissions, 'update users');

    const [isShowCreateModal, setIsShowCreateModal] = useState(false);

    const handleViewUser = (userId: number) => {
        router.visit(route('admin.users.show', { user: userId }));
    };

    const translations = useMemo(
        () => ({
            title: t('admin_pages.users.title'),
            allUsers: t('admin_pages.users.all_admins'),
            admin: t('admin_pages.roles.admin_role'),
            superAdmin: t('admin_pages.roles.super_admin_role'),
            subtitle: t('admin_pages.users.subtitle'),
            create: t('admin_pages.users.create'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout title={translations.subtitle} breadcrumb={breadcrumbs.users()}>
            <CreateUserModal
                roles={roles}
                isOpen={isShowCreateModal}
                forcedRole="admin"
                onClose={() => setIsShowCreateModal(false)}
            />
            <Stat.Group columns={3} className="mb-6">
                <Stat.Item title={translations.allUsers} value={users.total} icon={UserGroupIcon} />
                <Stat.Item title={translations.admin} value={adminCount} icon={ShieldCheckIcon} />
                <Stat.Item
                    title={translations.superAdmin}
                    value={superAdminCount}
                    icon={StarIcon}
                />
            </Stat.Group>
            <Section
                variant="flat"
                title={translations.subtitle}
                actions={
                    canCreateUsers && (
                        <Button onClick={() => setIsShowCreateModal(true)} size="sm">
                            {translations.create}
                        </Button>
                    )
                }
            >
                <UserList
                    data={users}
                    permissions={{ canUpdate: canUpdateUsers }}
                    onView={handleViewUser}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
