import { useMemo, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Badge, Button, DataTable, Section } from '@/Components';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatDate } from '@/utils';
import type { Enrollment, AssessmentAssignment, PageProps, PaginationType } from '@/types';
import type { DataTableConfig, FilterConfig } from '@/types/datatable';
import { DocumentTextIcon } from '@heroicons/react/24/outline';

interface ClassSubjectOption {
    id: number;
    subject_name: string;
    teacher_name: string;
}

interface Props extends PageProps {
    enrollment: Enrollment;
    assignments: PaginationType<AssessmentAssignment>;
    subjects: ClassSubjectOption[];
    filters: {
        search?: string;
        class_subject_id?: string;
        status?: string;
    };
}

export default function EnrollmentAssignmentsIndex({
    enrollment,
    assignments,
    subjects,
    filters,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const getStatusBadge = useCallback(
        (status: string) => {
            const statusMap: Record<
                string,
                { label: string; type: 'gray' | 'info' | 'warning' | 'success' }
            > = {
                not_submitted: {
                    label: t('components.assignment_list.status_not_started'),
                    type: 'gray',
                },
                in_progress: {
                    label: t('components.assignment_list.status_in_progress'),
                    type: 'info',
                },
                submitted: {
                    label: t('components.assignment_list.status_pending_grading'),
                    type: 'warning',
                },
                graded: { label: t('components.assignment_list.status_graded'), type: 'success' },
            };
            const cfg = statusMap[status] || statusMap.not_submitted;
            return <Badge label={cfg.label} type={cfg.type} size="sm" />;
        },
        [t],
    );

    const subjectFilterOptions: FilterConfig['options'] = useMemo(
        () => [
            { value: '', label: t('admin_pages.enrollments.all_subjects') },
            ...subjects.map((s) => ({ value: String(s.id), label: s.subject_name })),
        ],
        [subjects, t],
    );

    const statusFilterOptions: FilterConfig['options'] = useMemo(
        () => [
            { value: '', label: t('admin_pages.enrollments.all_assignment_statuses') },
            { value: 'graded', label: t('components.assignment_list.status_graded') },
            { value: 'submitted', label: t('components.assignment_list.status_pending_grading') },
            { value: 'in_progress', label: t('components.assignment_list.status_in_progress') },
            { value: 'not_submitted', label: t('components.assignment_list.status_not_started') },
        ],
        [t],
    );

    const tableConfig: DataTableConfig<AssessmentAssignment> = useMemo(
        () => ({
            columns: [
                {
                    key: 'title',
                    label: t('admin_pages.enrollments.assessment_title'),
                    render: (item: AssessmentAssignment) => (
                        <div>
                            <div className="font-medium text-gray-900">
                                {item.assessment?.title}
                            </div>
                            <div className="text-sm text-gray-500">{item.assessment?.type}</div>
                        </div>
                    ),
                },
                {
                    key: 'subject',
                    label: t('admin_pages.enrollments.subject'),
                    render: (item: AssessmentAssignment) => (
                        <span className="text-sm text-gray-700">
                            {item.assessment?.class_subject?.subject?.name || '-'}
                        </span>
                    ),
                },
                {
                    key: 'status',
                    label: t('admin_pages.enrollments.assignment_status'),
                    render: (item: AssessmentAssignment) => getStatusBadge(item.status),
                },
                {
                    key: 'score',
                    label: t('admin_pages.enrollments.score'),
                    render: (item: AssessmentAssignment) => {
                        if (item.score === null || item.score === undefined) {
                            return <span className="text-gray-400">-</span>;
                        }
                        const maxPoints =
                            item.assessment?.questions?.reduce(
                                (sum: number, q: { points: number }) => sum + q.points,
                                0,
                            ) ?? 0;
                        const percentage =
                            maxPoints > 0 ? Math.round((Number(item.score) / maxPoints) * 100) : 0;
                        return (
                            <div>
                                <div className="text-sm font-medium text-gray-900">
                                    {item.score} / {maxPoints}
                                </div>
                                <div className="text-xs text-gray-500">{percentage}%</div>
                            </div>
                        );
                    },
                },
                {
                    key: 'submitted_at',
                    label: t('admin_pages.enrollments.submitted_at'),
                    render: (item: AssessmentAssignment) =>
                        item.submitted_at ? (
                            <span className="text-sm text-gray-600">
                                {formatDate(item.submitted_at, 'datetime')}
                            </span>
                        ) : (
                            <span className="text-gray-400">-</span>
                        ),
                },
                {
                    key: 'actions',
                    label: '',
                    render: (item: AssessmentAssignment) => (
                        <Button
                            size="sm"
                            variant="outline"
                            color="secondary"
                            onClick={() =>
                                router.visit(
                                    route('admin.enrollments.assignments.show', {
                                        enrollment: enrollment.id,
                                        assignment: item.id,
                                    }),
                                )
                            }
                        >
                            {t('admin_pages.enrollments.view_details')}
                        </Button>
                    ),
                },
            ],
            filters: [
                {
                    key: 'class_subject_id',
                    label: t('admin_pages.enrollments.subject'),
                    type: 'select',
                    options: subjectFilterOptions,
                    defaultValue: filters.class_subject_id || '',
                },
                {
                    key: 'status',
                    label: t('admin_pages.enrollments.assignment_status'),
                    type: 'select',
                    options: statusFilterOptions,
                    defaultValue: filters.status || '',
                },
            ],
            searchPlaceholder: t('common.search'),
            perPageOptions: [15, 30, 50],
            emptyState: {
                icon: <DocumentTextIcon className="w-12 h-12" />,
                title: t('admin_pages.enrollments.no_assignments_title'),
                subtitle: t('admin_pages.enrollments.no_assignments_subtitle'),
            },
            emptySearchState: {
                icon: <DocumentTextIcon className="w-12 h-12" />,
                title: t('common.no_search_results'),
                subtitle: t('common.try_different_search'),
            },
        }),
        [t, subjectFilterOptions, statusFilterOptions, getStatusBadge, enrollment.id, filters],
    );

    const pageBreadcrumbs = [
        ...breadcrumbs.admin.showEnrollment(enrollment),
        { label: t('breadcrumbs.enrollment_assignments') },
    ];

    return (
        <AuthenticatedLayout
            title={t('admin_pages.enrollments.assignments_title')}
            breadcrumb={pageBreadcrumbs}
        >
            <div className="space-y-6">
                <Section
                    title={t('admin_pages.enrollments.assignments_title')}
                    subtitle={t('admin_pages.enrollments.assignments_subtitle', {
                        student: enrollment.student?.name || '',
                        class: enrollment.class?.name || '',
                    })}
                    actions={
                        <Button
                            size="sm"
                            variant="outline"
                            color="secondary"
                            onClick={() =>
                                router.visit(route('admin.enrollments.show', enrollment.id))
                            }
                        >
                            {t('admin_pages.enrollments.back_to_enrollment')}
                        </Button>
                    }
                >
                    <DataTable data={assignments} config={tableConfig} />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
