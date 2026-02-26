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
import { formatDate } from '@/utils';

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
                    value={overview.average_score !== null ? `${overview.average_score} / 20` : '—'}
                />
                <Stat.Item
                    icon={CheckCircleIcon}
                    title={t('teacher_class_pages.results.completion_rate')}
                    value={`${overview.completion_rate}%`}
                />
            </Stat.Group>

            <Section title={t('teacher_class_pages.results.assessment_stats')} className="mb-6">
                {assessment_stats.length === 0 ? (
                    <p className="py-6 text-center text-sm text-gray-500">
                        {t('teacher_class_pages.results.no_assessments')}
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-600">
                                        {t('teacher_class_pages.results.assessment_stats')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-600">
                                        {t('teacher_class_pages.results.subject')}
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-gray-600">
                                        {t('teacher_class_pages.results.scheduled_at')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.graded')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.submitted')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.in_progress')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.not_started')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.score')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.completion')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {assessment_stats.map((row) => (
                                    <tr key={row.id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {row.title}
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">
                                            {row.subject_name}
                                        </td>
                                        <td className="px-4 py-3 text-gray-500">
                                            {formatDate(row.scheduled_at ?? '', 'datetime')}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-900">
                                            {row.graded} / {row.total_assigned}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-600">
                                            {row.submitted}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-600">
                                            {row.in_progress}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-600">
                                            {row.not_started}
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium text-gray-900">
                                            {row.average_score !== null
                                                ? `${row.average_score} / 20`
                                                : '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <span
                                                className={
                                                    row.completion_rate >= 80
                                                        ? 'font-medium text-green-600'
                                                        : row.completion_rate >= 50
                                                          ? 'font-medium text-yellow-600'
                                                          : 'font-medium text-red-500'
                                                }
                                            >
                                                {row.completion_rate}%
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Section>

            <Section title={t('teacher_class_pages.results.student_stats')}>
                {student_stats.length === 0 ? (
                    <p className="py-6 text-center text-sm text-gray-500">
                        {t('teacher_class_pages.results.no_students')}
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-gray-600">
                                        {t('teacher_class_pages.results.student_stats')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.graded')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.submitted')}
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium text-gray-600">
                                        {t('teacher_class_pages.results.score')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 bg-white">
                                {student_stats.map((row) => (
                                    <tr key={row.enrollment_id} className="hover:bg-gray-50">
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-gray-900">
                                                {row.student_name}
                                            </div>
                                            <div className="text-xs text-gray-400">
                                                {row.student_email}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-900">
                                            {row.graded_count} / {overview.total_assessments}
                                        </td>
                                        <td className="px-4 py-3 text-right text-gray-600">
                                            {row.submitted_count}
                                        </td>
                                        <td className="px-4 py-3 text-right font-medium text-gray-900">
                                            {row.average_score !== null
                                                ? `${row.average_score} / 20`
                                                : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Section>
        </AuthenticatedLayout>
    );
}
