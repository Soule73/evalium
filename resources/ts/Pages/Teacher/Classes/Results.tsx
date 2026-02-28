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
import { AssessmentStatsTable, StudentStatsTable } from '@/Components/features/classes';

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

interface Props extends PageProps {
    class: ClassModel;
    results: Results;
}

/**
 * Class results page displaying aggregated assessment and student statistics.
 */
export default function TeacherClassResults({ class: classItem, results }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const { overview, assessment_stats, student_stats } = results;

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
