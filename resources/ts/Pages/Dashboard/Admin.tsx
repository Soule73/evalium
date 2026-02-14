import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type User } from '@/types';
import { BookOpenIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Stat } from '@/Components';


interface Stats {
    totalUsers: number;
    studentsCount: number;
    teachersCount: number;
    adminsCount: number;
}

interface Props {
    user: User;
    stats: Stats;
}

export default function AdminDashboard({ stats }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    return (
        <AuthenticatedLayout title={t('dashboard.title.admin')}
            breadcrumb={breadcrumbs.dashboard()}>
            <Stat.Group columns={3} className="mb-8" data-e2e="dashboard-content">
                <Stat.Item
                    title={t('dashboard.admin.total_users')}
                    value={stats.totalUsers}
                    icon={UserGroupIcon}
                />
                <Stat.Item
                    title={t('dashboard.admin.students')}
                    value={stats.studentsCount}
                    icon={BookOpenIcon}
                />
                <Stat.Item
                    title={t('dashboard.admin.teachers')}
                    value={stats.teachersCount}
                    icon={UserGroupIcon}
                />
            </Stat.Group>

        </AuthenticatedLayout >
    );
}