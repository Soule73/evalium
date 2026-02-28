import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { Deferred } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type Enrollment,
    type PageProps,
    type PaginationType,
    type SubjectGrade,
    type OverallStats,
} from '@/types';
import { Button, Section, TextEntry } from '@/Components';
import { SubjectGradeList } from '@/Components/shared/lists/SubjectGradeList';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatDate } from '@/utils';
import { ChartCard, ChartSkeleton, RadarChart } from '@/Components/ui/charts';

interface EnrollmentChartData {
    subjectRadar: Array<{ subject: string; grade: number | null; classAverage: number | null }>;
}

interface StudentEnrollmentShowProps extends PageProps {
    enrollment: Enrollment;
    subjects: PaginationType<SubjectGrade>;
    overallStats: OverallStats;
    chartData?: EnrollmentChartData;
}

export default function Show({
    enrollment,
    subjects,
    overallStats,
    chartData,
}: StudentEnrollmentShowProps) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            title: t('student_enrollment_pages.show.title'),
            subtitle: t('student_enrollment_pages.show.subtitle'),
            viewHistory: t('student_enrollment_pages.show.view_history'),
            viewClassmates: t('student_enrollment_pages.show.view_classmates'),
            currentClass: t('student_enrollment_pages.show.current_class'),
            className: t('student_enrollment_pages.show.class_name'),
            level: t('student_enrollment_pages.show.level'),
            academicYear: t('student_enrollment_pages.show.academic_year'),
            enrolledOn: t('student_enrollment_pages.show.enrolled_on'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.student.enrollment()}
        >
            <Section
                title={translations.title}
                subtitle={translations.subtitle}
                actions={
                    <div className="flex items-center space-x-4">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('student.enrollment.history'))}
                        >
                            {translations.viewHistory}
                        </Button>
                        <Button
                            size="sm"
                            onClick={() => router.visit(route('student.enrollment.classmates'))}
                        >
                            {translations.viewClassmates}
                        </Button>
                    </div>
                }
            >
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                    {translations.currentClass}
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <TextEntry
                        label={translations.className}
                        value={enrollment.class?.display_name ?? enrollment.class?.name ?? '-'}
                    />
                    <TextEntry
                        label={translations.level}
                        value={enrollment.class?.level?.name || '-'}
                    />
                    <TextEntry
                        label={translations.academicYear}
                        value={enrollment.class?.academic_year?.name || '-'}
                    />
                    <TextEntry
                        label={translations.enrolledOn}
                        value={formatDate(enrollment.enrolled_at)}
                    />
                </div>
            </Section>

            <Deferred data="chartData" fallback={<EnrollmentChartFallback />}>
                <EnrollmentChartSection chartData={chartData} />
            </Deferred>

            <SubjectGradeList
                subjects={subjects}
                overallStats={overallStats}
                variant="student"
                showSearch
            />
        </AuthenticatedLayout>
    );
}

function EnrollmentChartFallback() {
    return (
        <div className="mb-6">
            <ChartCard title="" loading>
                <ChartSkeleton />
            </ChartCard>
        </div>
    );
}

function EnrollmentChartSection({ chartData }: { chartData?: EnrollmentChartData }) {
    const { t } = useTranslations();

    if (!chartData || chartData.subjectRadar.length < 2) {
        return null;
    }

    const radarSeries = [
        { dataKey: 'grade', name: t('dashboard.student.your_grade'), color: '#6366f1' },
        { dataKey: 'classAverage', name: t('dashboard.student.class_average'), color: '#f59e0b' },
    ];

    return (
        <div className="mb-6">
            <ChartCard
                title={t('student_enrollment_pages.show.subject_radar')}
                subtitle={t('student_enrollment_pages.show.subject_radar_subtitle')}
            >
                <RadarChart
                    data={chartData.subjectRadar}
                    series={radarSeries}
                    maxValue={20}
                    height={300}
                />
            </ChartCard>
        </div>
    );
}
