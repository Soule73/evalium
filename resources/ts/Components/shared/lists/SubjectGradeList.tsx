import { useMemo, useCallback } from 'react';
import { Badge, Button, DataTable, Section, Stat } from '@/Components';
import type { SubjectGrade, OverallStats, PaginationType } from '@/types';
import type { DataTableConfig } from '@/types/datatable';
import { useTranslations } from '@/hooks';
import { AcademicCapIcon, BookOpenIcon, EyeIcon } from '@heroicons/react/24/outline';

interface SubjectGradeListProps {
    subjects: PaginationType<SubjectGrade>;
    overallStats: OverallStats;
    variant?: 'admin' | 'student';
    showSearch?: boolean;
    onSubjectClick?: (subject: SubjectGrade) => void;
}

const GRADE_THRESHOLDS = [
    {
        threshold: 16,
        labelKey: 'student_enrollment_pages.show.excellent',
        type: 'success' as const,
    },
    {
        threshold: 14,
        labelKey: 'student_enrollment_pages.show.very_good',
        type: 'success' as const,
    },
    { threshold: 12, labelKey: 'student_enrollment_pages.show.good', type: 'info' as const },
    {
        threshold: 10,
        labelKey: 'student_enrollment_pages.show.satisfactory',
        type: 'warning' as const,
    },
    {
        threshold: 0,
        labelKey: 'student_enrollment_pages.show.needs_improvement',
        type: 'error' as const,
    },
];

function getGradeColor(average: number | null): string {
    if (average === null) return 'text-gray-500';
    if (average >= 16) return 'text-green-600 font-semibold';
    if (average >= 14) return 'text-indigo-600 font-semibold';
    if (average >= 12) return 'text-yellow-600 font-medium';
    if (average >= 10) return 'text-orange-600 font-medium';
    return 'text-red-600 font-semibold';
}

/**
 * Shared component for displaying subject grade breakdown with overall statistics.
 *
 * Used by both Admin Enrollment Show and Student Enrollment Show pages.
 * Supports admin and student variants for minor display differences.
 */
export function SubjectGradeList({
    subjects,
    overallStats,
    showSearch = false,
    onSubjectClick,
}: SubjectGradeListProps) {
    const { t } = useTranslations();
    const pendingAssessments = overallStats.total_assessments - overallStats.completed_assessments;

    const getGradeBadge = useCallback(
        (average: number | null) => {
            if (average === null) return null;
            const gradeInfo = GRADE_THRESHOLDS.find(({ threshold }) => average >= threshold);
            return gradeInfo ? (
                <Badge label={t(gradeInfo.labelKey)} type={gradeInfo.type} size="sm" />
            ) : null;
        },
        [t],
    );

    const subjectsTableConfig: DataTableConfig<SubjectGrade> = useMemo(
        () => ({
            columns: [
                {
                    key: 'subject_name',
                    label: t('student_enrollment_pages.show.subject'),
                    sortable: true,
                },
                {
                    key: 'teacher_name',
                    label: t('student_enrollment_pages.show.teacher'),
                    sortable: true,
                    render: (item: SubjectGrade) => item.teacher_name || '-',
                },
                {
                    key: 'assessments',
                    label: t('student_enrollment_pages.show.assessments'),
                    sortable: false,
                    render: (item: SubjectGrade) => (
                        <span className="text-sm text-gray-600">
                            {item.completed_count} / {item.assessments_count}
                        </span>
                    ),
                },
                {
                    key: 'average',
                    label: t('student_enrollment_pages.show.average'),
                    sortable: true,
                    render: (item: SubjectGrade) => (
                        <div className="flex items-center justify-start space-x-2">
                            <span className={getGradeColor(item.average)}>
                                {item.average !== null && item.average !== undefined
                                    ? `${Number(item.average).toFixed(2)}/20`
                                    : '-'}
                            </span>
                            {getGradeBadge(item.average)}
                        </div>
                    ),
                },
                ...(onSubjectClick
                    ? [
                          {
                              key: 'actions',
                              label: t('common.actions'),
                              sortable: false,
                              render: (item: SubjectGrade) => (
                                  <Button
                                      type="button"
                                      size="xs"
                                      variant="ghost"
                                      onClick={() => onSubjectClick(item)}
                                  >
                                      <EyeIcon className="h-4 w-4" />
                                      {t('common.view')}
                                  </Button>
                              ),
                          },
                      ]
                    : []),
            ],
            perPageOptions: [10, 25, 50],
            ...(showSearch && {
                searchPlaceholder: t('common.search'),
            }),
            emptyState: {
                icon: <BookOpenIcon className="w-12 h-12" />,
                title: t('student_enrollment_pages.show.no_subjects_title'),
                subtitle: t('student_enrollment_pages.show.no_subjects_subtitle'),
            },
            emptySearchState: {
                icon: <BookOpenIcon className="w-12 h-12" />,
                title: t('common.no_search_results'),
                subtitle: t('common.try_different_search'),
            },
        }),
        [t, showSearch, getGradeBadge, onSubjectClick],
    );

    return (
        <>
            <Section title={t('student_enrollment_pages.show.overall_statistics')}>
                <Stat.Group columns={4}>
                    <Stat.Item
                        title={t('student_enrollment_pages.show.overall_average')}
                        value={
                            overallStats.annual_average !== null
                                ? `${Number(overallStats.annual_average).toFixed(2)}/20`
                                : t('student_enrollment_pages.show.no_grade')
                        }
                        icon={AcademicCapIcon}
                    />
                    <Stat.Item
                        title={t('student_enrollment_pages.show.total_assessments')}
                        value={overallStats.total_assessments}
                        icon={BookOpenIcon}
                    />
                    <Stat.Item
                        title={t('student_enrollment_pages.show.completed_assessments')}
                        value={overallStats.completed_assessments}
                        icon={BookOpenIcon}
                    />
                    <Stat.Item
                        title={t('student_enrollment_pages.show.pending_assessments')}
                        value={pendingAssessments}
                        icon={BookOpenIcon}
                    />
                </Stat.Group>
            </Section>

            <Section title={t('student_enrollment_pages.show.grade_breakdown')}>
                <DataTable data={subjects} config={subjectsTableConfig} />
            </Section>
        </>
    );
}
