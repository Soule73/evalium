import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    ChartBarIcon,
    CheckIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
} from '@heroicons/react/24/outline';
import { Deferred } from '@inertiajs/react';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Stat } from '@/Components';
import {
    BarChart,
    ChartCard,
    ChartSkeleton,
    DonutChart,
    LineChart,
    RadarChart,
} from '@/Components/ui/charts';

interface DashboardStats {
    totalAssessments: number;
    completedAssessments: number;
    pendingAssessments: number;
    averageScore: number | null;
}

interface ChartData {
    subjectRadar: Array<{ subject: string; grade: number | null; classAverage: number | null }>;
    assessmentStatus: Array<{ name: string; value: number; color: string }>;
    recentScores: Array<{ name: string; value: number | null }>;
    gradeTrend: Array<{ name: string; value: number | null }>;
}

interface Props {
    stats: DashboardStats;
    chartData?: ChartData;
}

export default function StudentDashboard({ stats, chartData }: Props) {
    const { t } = useTranslations();

    const averageDisplay =
        stats.averageScore !== null
            ? `${stats.averageScore}${t('dashboard.student.out_of_20')}`
            : t('dashboard.student.no_grades');

    return (
        <AuthenticatedLayout title={t('dashboard.title.student')}>
            <Stat.Group columns={4} className="mb-8" data-e2e="dashboard-content">
                <Stat.Item
                    title={t('dashboard.student.total_assessments')}
                    value={stats.totalAssessments}
                    icon={ClipboardDocumentListIcon}
                />
                <Stat.Item
                    title={t('dashboard.student.pending_assessments')}
                    value={stats.pendingAssessments}
                    icon={ClockIcon}
                />
                <Stat.Item
                    title={t('dashboard.student.completed_assessments')}
                    value={stats.completedAssessments}
                    icon={CheckIcon}
                />
                <Stat.Item
                    title={t('dashboard.student.average_score')}
                    value={averageDisplay}
                    icon={ChartBarIcon}
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
        <div className="mb-8 space-y-6">
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
            </div>
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <ChartCard title="" loading>
                    <ChartSkeleton />
                </ChartCard>
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

    const hasRadarData = chartData.subjectRadar.length > 0;
    const hasStatusData = chartData.assessmentStatus.some((d) => d.value > 0);
    const hasScoresData = chartData.recentScores.length > 0;
    const hasTrendData = chartData.gradeTrend.length > 1;

    const totalAssessments = chartData.assessmentStatus.reduce((sum, d) => sum + d.value, 0);

    return (
        <div className="mb-8 space-y-6">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <ChartCard
                    title={t('dashboard.student.subject_overview')}
                    subtitle={t('dashboard.student.subject_overview_subtitle')}
                    className="lg:col-span-3"
                    empty={!hasRadarData}
                    emptyMessage={t('charts.no_data')}
                >
                    <RadarChart
                        data={chartData.subjectRadar}
                        series={[
                            {
                                dataKey: 'grade',
                                name: t('dashboard.student.your_grade'),
                                color: '#4f46e5',
                            },
                            {
                                dataKey: 'classAverage',
                                name: t('dashboard.student.class_average'),
                                color: '#6b7280',
                                fillOpacity: 0.1,
                            },
                        ]}
                        maxValue={20}
                        height={280}
                        formatTooltipValue={(v) => `${v}/20`}
                    />
                </ChartCard>

                <ChartCard
                    title={t('dashboard.student.assessment_status')}
                    subtitle={t('dashboard.student.assessment_status_subtitle')}
                    className="lg:col-span-2"
                    empty={!hasStatusData}
                    emptyMessage={t('charts.no_data')}
                >
                    <DonutChart
                        data={chartData.assessmentStatus}
                        height={280}
                        centerLabel={t('dashboard.student.total_assessments')}
                        centerValue={totalAssessments}
                    />
                </ChartCard>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <ChartCard
                    title={t('dashboard.student.recent_scores')}
                    subtitle={t('dashboard.student.recent_scores_subtitle')}
                    empty={!hasScoresData}
                    emptyMessage={t('charts.no_data')}
                >
                    <BarChart
                        data={chartData.recentScores.map((d) => ({
                            ...d,
                            value: d.value ?? undefined,
                        }))}
                        colorByItem
                        height={250}
                        formatTooltipValue={(v) => `${v}/20`}
                    />
                </ChartCard>

                <ChartCard
                    title={t('dashboard.student.grade_trend')}
                    subtitle={t('dashboard.student.grade_trend_subtitle')}
                    empty={!hasTrendData}
                    emptyMessage={t('charts.no_data')}
                >
                    <LineChart
                        data={chartData.gradeTrend}
                        series={[{ dataKey: 'value', name: t('dashboard.student.average_score') }]}
                        yDomain={[0, 20]}
                        height={250}
                        formatTooltipValue={(v) => `${v}/20`}
                    />
                </ChartCard>
            </div>
        </div>
    );
}
