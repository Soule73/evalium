import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    AcademicCapIcon,
    BookOpenIcon,
    ChartBarIcon,
    ClipboardDocumentListIcon,
    PlayCircleIcon,
} from '@heroicons/react/24/outline';
import { Deferred } from '@inertiajs/react';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Stat } from '@/Components';
import {
    BarChart,
    ChartCard,
    ChartSkeleton,
    CompletionChart,
    ScoreDistribution,
} from '@/Components/ui/charts';

interface Stats {
    total_classes: number;
    total_subjects: number;
    total_assessments: number;
    in_progress_assessments: number;
    overall_average: number | null;
}

interface ChartData {
    completionOverview: {
        graded: number;
        submitted: number;
        in_progress: number;
        not_started: number;
    };
    scoreDistribution: Array<{ range: string; count: number }>;
    classPerformance: Array<{ name: string; value: number | null }>;
}

interface Props {
    stats: Stats;
    chartData?: ChartData;
}

export default function TeacherDashboard({ stats, chartData }: Props) {
    const { t } = useTranslations();

    const averageDisplay =
        stats.overall_average !== null
            ? `${stats.overall_average}${t('dashboard.teacher.out_of_20')}`
            : t('dashboard.teacher.no_grades');

    return (
        <AuthenticatedLayout title={t('dashboard.title.teacher')}>
            <Stat.Group columns={5} className="mb-8" data-e2e="dashboard-content">
                <Stat.Item
                    title={t('dashboard.teacher.total_classes')}
                    value={stats.total_classes}
                    icon={AcademicCapIcon}
                />
                <Stat.Item
                    title={t('dashboard.teacher.total_subjects')}
                    value={stats.total_subjects}
                    icon={BookOpenIcon}
                />
                <Stat.Item
                    title={t('dashboard.teacher.total_assessments')}
                    value={stats.total_assessments}
                    icon={ClipboardDocumentListIcon}
                />
                <Stat.Item
                    title={t('dashboard.teacher.in_progress_assessments')}
                    value={stats.in_progress_assessments}
                    icon={PlayCircleIcon}
                />
                <Stat.Item
                    title={t('dashboard.teacher.overall_average')}
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
        <div className="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <ChartCard title="" loading>
                <ChartSkeleton />
            </ChartCard>
            <ChartCard title="" loading>
                <ChartSkeleton />
            </ChartCard>
            <ChartCard title="" loading>
                <ChartSkeleton />
            </ChartCard>
        </div>
    );
}

function ChartsSection({ chartData }: { chartData?: ChartData }) {
    const { t } = useTranslations();

    if (!chartData) {
        return null;
    }

    const completionData = [
        {
            name: t('charts.completion.total'),
            graded: chartData.completionOverview.graded,
            submitted: chartData.completionOverview.submitted,
            in_progress: chartData.completionOverview.in_progress,
            not_started: chartData.completionOverview.not_started,
        },
    ];

    const hasCompletionData = Object.values(chartData.completionOverview).some((v) => v > 0);
    const hasScoreData = chartData.scoreDistribution.some((d) => d.count > 0);
    const hasClassData = chartData.classPerformance.length > 0;

    return (
        <div className="mb-8 space-y-6">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <ChartCard
                    title={t('dashboard.teacher.completion_overview')}
                    subtitle={t('dashboard.teacher.completion_overview_subtitle')}
                    empty={!hasCompletionData}
                    emptyMessage={t('charts.no_data')}
                >
                    <CompletionChart data={completionData} height={220} />
                </ChartCard>

                <ChartCard
                    title={t('dashboard.teacher.score_distribution')}
                    subtitle={t('dashboard.teacher.score_distribution_subtitle')}
                    empty={!hasScoreData}
                    emptyMessage={t('charts.no_data')}
                >
                    <ScoreDistribution data={chartData.scoreDistribution} height={220} />
                </ChartCard>
            </div>

            <ChartCard
                title={t('dashboard.teacher.class_performance')}
                subtitle={t('dashboard.teacher.class_performance_subtitle')}
                empty={!hasClassData}
                emptyMessage={t('charts.no_data')}
            >
                <BarChart
                    data={chartData.classPerformance.map((d) => ({
                        ...d,
                        value: d.value ?? undefined,
                    }))}
                    colorByItem
                    height={280}
                    formatTooltipValue={(v) => `${v}/20`}
                />
            </ChartCard>
        </div>
    );
}
