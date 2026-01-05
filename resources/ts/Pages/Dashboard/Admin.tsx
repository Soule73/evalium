import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { User } from '@/types';
import { BookOpenIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { StatCard } from '@/Components';


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
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" data-e2e='dashboard-content'>
                <StatCard
                    title={trans('dashboard.admin.total_users')}
                    value={stats.totalUsers}
                    icon={
                        UserGroupIcon
                    }
                    color="blue"
                />
                <StatCard
                    title={trans('dashboard.admin.students')}
                    value={stats.studentsCount}
                    color='green'
                    icon={
                        BookOpenIcon
                    }
                />

                <StatCard
                    title={trans('dashboard.admin.teachers')}
                    value={stats.teachersCount}
                    color='purple'
                    icon={
                        UserGroupIcon
                    }
                />
            </div>

        </AuthenticatedLayout >
    );
}