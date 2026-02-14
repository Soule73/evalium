import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type User } from '@/types';
import { BookOpenIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
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

    return (
        <AuthenticatedLayout title={trans('dashboard.title.admin')}
            breadcrumb={breadcrumbs.dashboard()}>
            <Stat.Group columns={3} className="mb-8" data-e2e="dashboard-content">
                <Stat.Item
                    title={trans('dashboard.admin.total_users')}
                    value={stats.totalUsers}
                    icon={UserGroupIcon}
                />
                <Stat.Item
                    title={trans('dashboard.admin.students')}
                    value={stats.studentsCount}
                    icon={BookOpenIcon}
                />
                <Stat.Item
                    title={trans('dashboard.admin.teachers')}
                    value={stats.teachersCount}
                    icon={UserGroupIcon}
                />
            </Stat.Group>

        </AuthenticatedLayout >
    );
}