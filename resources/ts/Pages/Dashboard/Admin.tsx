import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { User } from '@/types';
import StatCard from '@/Components/StatCard';
import { BookOpenIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';


interface Stats {
    total_users: number;
    students_count: number;
    teachers_count: number;
}

interface Props {
    user: User;
    stats: Stats;
}

export default function AdminDashboard({ stats }: Props) {

    return (
        <AuthenticatedLayout title={trans('dashboard.title.admin')}
            breadcrumb={breadcrumbs.dashboard()}
        >
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <StatCard
                    title={trans('dashboard.admin.total_users')}
                    value={stats.total_users}
                    icon={
                        UserGroupIcon
                    }
                    color="blue"
                />
                <StatCard
                    title={trans('dashboard.admin.students')}
                    value={stats.students_count}
                    color='green'
                    icon={
                        BookOpenIcon
                    }
                />

                <StatCard
                    title={trans('dashboard.admin.teachers')}
                    value={stats.teachers_count}
                    color='purple'
                    icon={
                        UserGroupIcon
                    }
                />
            </div>

        </AuthenticatedLayout >
    );
}