import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Section, Stat } from '@/Components';
import { type ClassModel, type PageProps } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import {
    UserGroupIcon,
    ClipboardDocumentListIcon,
    ChartBarIcon,
    CheckCircleIcon,
} from '@heroicons/react/24/outline';
import { Deferred } from '@inertiajs/react';
import { AssessmentStatsTable, StudentStatsTable } from '@/Components/features/classes';
import {
    ChartCard,
    ChartSkeleton,
    CompletionChart,
    LineChart,
    ScoreDistribution,
} from '@/Components/ui/charts';

interface Overview {
    total_students: number;
    total_assessments: number;
    average_score: number | null;
    completion_rate: number;
}

interface AssessmentStat {
    id: number;
    title: string;
    type: string;
    scheduled_at: string | null;
    subject_name: string;
    total_assigned: number;
    graded: number;
    submitted: number;
    in_progress: number;
    not_started: number;
    average_score: number | null;
    completion_rate: number;
}

interface StudentStat {
    enrollment_id: number;
    student_name: string;
    student_email: string;
    graded_count: number;
    submitted_count: number;
    average_score: number | null;
}

interface Results {
    overview: Overview;
    assessment_stats: AssessmentStat[];
    student_stats: StudentStat[];
}

interface ChartData {
    scoreDistribution: Array<{ range: string; count: number }>;
    assessmentTrend: Array<{ name: string; value: number | null }>;
}

interface Props extends PageProps {
    class: ClassModel;
    results: Results;
    chartData?: ChartData;
}

/**
 * Class results page displaying aggregated assessment and student statistics.
 */
export default function TeacherClassResults({ class: classItem, results, chartData }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const { overview, assessment_stats, student_stats } = results;

    const completionData = assessment_stats.map((a) => ({
        name: a.title,
        graded: a.graded,
        submitted: a.submitted,
        in_progress: a.in_progress,
        not_started: a.not_started,
    }));

    const hasCompletionData = assessment_stats.length > 0;

    return (
        <AuthenticatedLayout
            title={t('teacher_class_pages.results.title')}
            breadcrumb={breadcrumbs.teacher.classResults(classItem)}
        >
            <Stat.Group columns={4} className="mb-8">
                <Stat.Item
                    icon={UserGroupIcon}
                    title={t('teacher_class_pages.results.total_students')}
                    value={overview.total_students}
                />
                <Stat.Item
                    icon={ClipboardDocumentListIcon}
                    title={t('teacher_class_pages.results.total_assessments')}
                    value={overview.total_assessments}
                />
                <Stat.Item
                    icon={ChartBarIcon}
                    title={t('teacher_class_pages.results.average_score')}
                    value={
                        overview.average_score !== null
                            ? `${overview.average_score} / 20`
                            : '\u2014'
                    }
                />
                <Stat.Item
                    icon={CheckCircleIcon}
                    title={t('teacher_class_pages.results.completion_rate')}
                    value={`${overview.completion_rate}%`}
                />
            </Stat.Group>

            <ChartCard
                title={t('teacher_class_pages.results.assessment_completion')}
                subtitle={t('teacher_class_pages.results.assessment_completion_subtitle')}
                empty={!hasCompletionData}
                emptyMessage={t('charts.no_data')}
                className="mb-6"
            >
                <CompletionChart
                    data={completionData}
                    height={Math.max(220, assessment_stats.length * 40)}
                    layout="vertical"
                />
            </ChartCard>

            <Deferred data="chartData" fallback={<ChartsFallback />}>
                <ChartsSection chartData={chartData} />
            </Deferred>

            <Section title={t('teacher_class_pages.results.assessment_stats')} className="mb-6">
                <AssessmentStatsTable stats={assessment_stats} />
            </Section>

            <Section title={t('teacher_class_pages.results.student_stats')}>
                <StudentStatsTable
                    stats={student_stats}
                    totalAssessments={overview.total_assessments}
                />
            </Section>
        </AuthenticatedLayout>
    );
}

function ChartsFallback() {
    return (
        <div className="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
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

    const hasScoreData = chartData.scoreDistribution.some((d) => d.count > 0);
    const hasTrendData = chartData.assessmentTrend.length > 0;

    return (
        <div className="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <ChartCard
                title={t('teacher_class_pages.results.score_distribution')}
                subtitle={t('teacher_class_pages.results.score_distribution_subtitle')}
                empty={!hasScoreData}
                emptyMessage={t('charts.no_data')}
            >
                <ScoreDistribution data={chartData.scoreDistribution} height={220} />
            </ChartCard>

            <ChartCard
                title={t('teacher_class_pages.results.average_trend')}
                subtitle={t('teacher_class_pages.results.average_trend_subtitle')}
                empty={!hasTrendData}
                emptyMessage={t('charts.no_data')}
            >
                <LineChart
                    data={chartData.assessmentTrend.map((d) => ({
                        ...d,
                        value: d.value ?? undefined,
                    }))}
                    height={220}
                    yDomain={[0, 20]}
                    formatTooltipValue={(v) => `${v}/20`}
                />
            </ChartCard>
        </div>
    );
}
