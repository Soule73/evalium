import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { UserGroupIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';
import { type User, type PageProps } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { hasPermission } from '@/utils';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Stat, Section, Button, CreateUserModal } from '@/Components';
import { UserList } from '@/Components/shared/lists';

interface Props extends PageProps {
    teachers: PaginationType<User>;
    activeCount: number;
    inactiveCount: number;
}

export default function TeachersIndex({ auth, teachers, activeCount, inactiveCount }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const canCreateUsers = hasPermission(auth.permissions, 'create users');
    const canUpdateUsers = hasPermission(auth.permissions, 'update users');

    const [isShowCreateModal, setIsShowCreateModal] = useState(false);

    const handleViewTeacher = (userId: number) => {
        router.visit(route('admin.teachers.show', { user: userId }));
    };

    const translations = useMemo(
        () => ({
            title: t('admin_pages.teachers.title'),
            allTeachers: t('admin_pages.teachers.all_teachers'),
            activeTeachers: t('admin_pages.teachers.active_teachers'),
            inactiveTeachers: t('admin_pages.teachers.inactive_teachers'),
            subtitle: t('admin_pages.teachers.subtitle'),
            create: t('admin_pages.teachers.create'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.admin.teachers()}>
            <CreateUserModal
                isOpen={isShowCreateModal}
                onClose={() => setIsShowCreateModal(false)}
                forcedRole="teacher"
                storeRoute="admin.teachers.store"
            />

            <Stat.Group columns={3} className="mb-6">
                <Stat.Item
                    title={translations.allTeachers}
                    value={teachers.total}
                    icon={UserGroupIcon}
                />
                <Stat.Item
                    title={translations.activeTeachers}
                    value={activeCount}
                    icon={CheckCircleIcon}
                />
                <Stat.Item
                    title={translations.inactiveTeachers}
                    value={inactiveCount}
                    icon={XCircleIcon}
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
                    data={teachers}
                    permissions={{ canUpdate: canUpdateUsers }}
                    onView={handleViewTeacher}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
