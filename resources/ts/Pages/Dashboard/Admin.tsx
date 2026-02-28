import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type User } from '@/types';
import {
    AcademicCapIcon,
    BookOpenIcon,
    ClipboardDocumentListIcon,
    UserGroupIcon,
} from '@heroicons/react/24/outline';
import { Deferred } from '@inertiajs/react';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Stat } from '@/Components';
import { BarChart, ChartCard, ChartSkeleton, DonutChart } from '@/Components/ui/charts';

interface Stats {
    totalUsers: number;
    studentsCount: number;
    teachersCount: number;
    adminsCount: number;
    classesCount: number;
    enrollmentsCount: number;
    assessmentsCount: number;
    publishedCount: number;
    draftCount: number;
}

interface ChartData {
    usersByRole: Array<{ name: string; value: number; color: string }>;
    classesByLevel: Array<{ name: string; value: number }>;
    enrollmentCapacity: Array<{ name: string; enrolled: number; capacity: number }>;
}

interface Props {
    user: User;
    stats: Stats;
    chartData?: ChartData;
}

export default function AdminDashboard({ stats, chartData }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    return (
        <AuthenticatedLayout
            title={t('dashboard.title.admin')}
            breadcrumb={breadcrumbs.dashboard()}
        >
            <Stat.Group columns={5} className="mb-8" data-e2e="dashboard-content">
                <Stat.Item
                    title={t('dashboard.admin.total_users')}
                    value={stats.totalUsers}
                    icon={UserGroupIcon}
                />
                <Stat.Item
                    title={t('dashboard.admin.students')}
                    value={stats.studentsCount}
                    icon={AcademicCapIcon}
                />
                <Stat.Item
                    title={t('dashboard.admin.teachers')}
                    value={stats.teachersCount}
                    icon={BookOpenIcon}
                />
                <Stat.Item
                    title={t('dashboard.admin.classes')}
                    value={stats.classesCount}
                    icon={UserGroupIcon}
                />
                <Stat.Item
                    title={t('dashboard.admin.assessments')}
                    value={stats.assessmentsCount}
                    icon={ClipboardDocumentListIcon}
                    description={`${stats.publishedCount} ${t('dashboard.admin.published')} / ${stats.draftCount} ${t('dashboard.admin.draft')}`}
                />
            </Stat.Group>

            <Deferred data="chartData" fallback={<ChartsFallback />}>
                <ChartsSection chartData={chartData} />
            </Deferred>
        </AuthenticatedLayout>
    );
}

function ChartsFallback() {
    return (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <div className="lg:col-span-3">
                <ChartCard title="" loading>
                    <ChartSkeleton />
                </ChartCard>
            </div>
            <div className="lg:col-span-2">
                <ChartCard title="" loading>
                    <ChartSkeleton />
                </ChartCard>
            </div>
            <div className="lg:col-span-5">
                <ChartCard title="" loading>
                    <ChartSkeleton />
                </ChartCard>
            </div>
        </div>
    );
}

function ChartsSection({ chartData }: { chartData?: ChartData }) {
    const { t } = useTranslations();

    if (!chartData) {
        return null;
    }

    return (
        <div className="space-y-6">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <ChartCard
                    title={t('dashboard.admin.users_by_role')}
                    className="lg:col-span-2"
                    empty={chartData.usersByRole.length === 0}
                    emptyMessage={t('charts.no_data')}
                >
                    <DonutChart
                        data={chartData.usersByRole}
                        showLabels
                        centerLabel={t('dashboard.admin.total_users')}
                        centerValue={chartData.usersByRole.reduce((sum, d) => sum + d.value, 0)}
                    />
                </ChartCard>

                <ChartCard
                    title={t('dashboard.admin.classes_by_level')}
                    className="lg:col-span-3"
                    empty={chartData.classesByLevel.length === 0}
                    emptyMessage={t('charts.no_data')}
                >
                    <BarChart data={chartData.classesByLevel} colorByItem height={280} />
                </ChartCard>
            </div>

            <ChartCard
                title={t('dashboard.admin.enrollment_capacity')}
                subtitle={t('dashboard.admin.enrollment_capacity_subtitle')}
                empty={chartData.enrollmentCapacity.length === 0}
                emptyMessage={t('charts.no_data')}
            >
                <BarChart
                    data={chartData.enrollmentCapacity}
                    series={[
                        {
                            dataKey: 'enrolled',
                            name: t('dashboard.admin.enrolled'),
                            color: '#4f46e5',
                        },
                        {
                            dataKey: 'capacity',
                            name: t('dashboard.admin.capacity'),
                            color: '#e5e7eb',
                        },
                    ]}
                    height={300}
                    showLegend
                />
            </ChartCard>
        </div>
    );
}
