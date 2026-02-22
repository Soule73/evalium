import { useState, useMemo, useCallback, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type Assessment, type AssessmentAssignment, type Enrollment } from '@/types';
import { Badge, MarkdownRenderer, Toggle } from '@evalium/ui';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks';
import { useFormatters } from '@/hooks/shared/useFormatters';
import type {
    ColumnConfig,
    ActionConfig,
    FilterConfig,
    EntityListVariant,
} from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface ClassSubjectOption {
    id: number;
    subject_name: string;
    teacher_name: string;
}

interface ClassOption {
    id: number;
    name: string;
}

interface SimpleFilterOption {
    id: number;
    name: string;
}

interface AssessmentListProps {
    data: PaginationType<AssessmentItem>;
    variant?: 'admin' | 'teacher' | 'student' | 'class-assignment';
    onView?: (item: Assessment | AssessmentAssignment) => void;
    showPagination?: boolean;
    showClassColumn?: boolean;
    subjects?: ClassSubjectOption[];
    classes?: ClassOption[];
    enrollment?: Enrollment;
    filterSubjects?: SimpleFilterOption[];
    filterTeachers?: SimpleFilterOption[];
}

type AssessmentItem = Assessment | AssessmentAssignment;
type AssignmentWithAssessment = AssessmentAssignment & { assessment: Assessment };
type TranslateFn = (key: string) => string;

function isAssignmentVariant(v: EntityListVariant | undefined): boolean {
    return v === 'student' || v === 'class-assignment';
}

function getStatusBadge(status: string, t: TranslateFn): ReactNode {
    const statusMap: Record<
        string,
        { label: string; type: 'warning' | 'info' | 'success' | 'gray' }
    > = {
        not_submitted: { label: t('student_assessment_pages.index.not_started'), type: 'warning' },
        in_progress: { label: t('student_assessment_pages.index.in_progress'), type: 'info' },
        submitted: { label: t('student_assessment_pages.index.completed'), type: 'success' },
        graded: { label: t('student_assessment_pages.index.graded'), type: 'success' },
    };
    const cfg = statusMap[status] || { label: status, type: 'gray' as const };
    return <Badge label={cfg.label} type={cfg.type} size="sm" />;
}

function getDeliveryModeBadge(deliveryMode: string, t: TranslateFn): ReactNode {
    const isHomework = deliveryMode === 'homework';
    return (
        <Badge
            label={
                isHomework
                    ? t('student_assessment_pages.index.delivery_mode_homework')
                    : t('student_assessment_pages.index.delivery_mode_supervised')
            }
            type={isHomework ? 'info' : 'gray'}
            size="sm"
        />
    );
}

interface ColumnDeps {
    t: TranslateFn;
    variant: AssessmentListProps['variant'];
    showClassColumn: boolean;
    formatDuration: (minutes: number) => string;
    handleToggleStatus: (id: number, isPublished: boolean) => void;
}

function buildColumns(deps: ColumnDeps): ColumnConfig<AssessmentItem>[] {
    const { t, variant, showClassColumn, formatDuration, handleToggleStatus } = deps;

    const titleColumn: ColumnConfig<AssessmentItem> = {
        key: 'title',
        labelKey:
            variant === 'student'
                ? 'student_assessment_pages.index.title'
                : 'components.assessment_list.assessment_label',
        render: (item, currentVariant) => {
            if (isAssignmentVariant(currentVariant)) {
                const assignment = item as AssignmentWithAssessment;
                return (
                    <div>
                        <span className="font-medium text-gray-900">
                            {assignment.assessment.title}
                        </span>
                        <div className="flex items-center gap-2">
                            {getDeliveryModeBadge(assignment.assessment.delivery_mode, t)}
                            {variant === 'student' && (
                                <span className="text-sm text-gray-500">
                                    {' '}
                                    - {assignment.assessment.questions_count || 0}{' '}
                                    {t('assessment_pages.common.questions')}
                                </span>
                            )}
                        </div>
                    </div>
                );
            }
            const assessment = item as Assessment;
            return (
                <div>
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium text-gray-900">
                            {assessment.title}
                        </span>
                        {getDeliveryModeBadge(assessment.delivery_mode, t)}
                    </div>
                    {assessment.description && (
                        <div className="text-sm text-gray-500 truncate max-w-sm line-clamp-2">
                            <MarkdownRenderer>{assessment.description}</MarkdownRenderer>
                        </div>
                    )}
                </div>
            );
        },
    };

    const subjectColumn: ColumnConfig<AssessmentItem> = {
        key: 'subject',
        labelKey: 'student_assessment_pages.index.subject',
        render: (item) => {
            const assignment = item as AssignmentWithAssessment;
            return (
                <div>
                    <span className="text-sm font-medium">
                        {assignment.assessment.class_subject?.subject?.name || '-'}
                    </span>
                    <div className="text-sm text-gray-500 truncate max-w-sm line-clamp-2">
                        {t('student_assessment_pages.index.teacher')}:{' '}
                        {assignment.assessment.class_subject?.teacher?.name || '-'}
                    </div>
                </div>
            );
        },
        conditional: (cv) => isAssignmentVariant(cv),
    };

    const dateColumn: ColumnConfig<AssessmentItem> = {
        key: 'assessment_date',
        labelKey: 'student_assessment_pages.index.assessment_date',
        render: (item) => {
            const assignment = item as AssignmentWithAssessment;
            const isHomework =
                assignment.assessment.delivery_mode === 'homework' &&
                assignment.assessment.due_date;
            const dateValue = isHomework
                ? assignment.assessment.due_date
                : assignment.assessment.scheduled_at;
            const dateLabel = isHomework
                ? t('student_assessment_pages.show.due_date')
                : t('student_assessment_pages.show.scheduled_date');
            return (
                <div>
                    <div className="text-xs text-gray-500">{dateLabel}</div>
                    <span className="text-gray-700">{formatDate(dateValue ?? '', 'datetime')}</span>
                </div>
            );
        },
        conditional: (cv) => isAssignmentVariant(cv),
    };

    const statusColumn: ColumnConfig<AssessmentItem> = {
        key: 'status',
        labelKey: 'student_assessment_pages.index.status',
        render: (item) => {
            const assignment = item as AssignmentWithAssessment;
            return getStatusBadge(assignment.status, t);
        },
        conditional: (cv) => isAssignmentVariant(cv),
    };

    const scoreColumn: ColumnConfig<AssessmentItem> = {
        key: 'score',
        labelKey: 'admin_pages.enrollments.score',
        render: (item) => {
            const assignment = item as AssignmentWithAssessment;
            if (assignment.score === null || assignment.score === undefined) {
                return <span className="text-gray-400">-</span>;
            }
            const maxPoints =
                assignment.assessment?.questions?.reduce(
                    (sum: number, q: { points: number }) => sum + q.points,
                    0,
                ) ?? 0;
            const percentage =
                maxPoints > 0 ? Math.round((Number(assignment.score) / maxPoints) * 100) : 0;
            return (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {assignment.score} / {maxPoints}
                    </div>
                    <div className="text-xs text-gray-500">{percentage}%</div>
                </div>
            );
        },
        conditional: (cv) => cv === 'class-assignment',
    };

    const durationColumn: ColumnConfig<AssessmentItem> = {
        key: 'duration',
        labelKey: 'components.assessment_list.duration_label',
        render: (item, currentVariant) => {
            const durationMinutes =
                currentVariant === 'class-assignment'
                    ? (item as AssessmentAssignment).assessment?.duration_minutes
                    : (item as Assessment).duration_minutes;
            return (
                <span className="text-sm text-gray-900">
                    {formatDuration(durationMinutes || 0)}
                </span>
            );
        },
        conditional: (cv) => cv !== 'student',
    };

    const classSubjectColumn: ColumnConfig<AssessmentItem> = {
        key: 'class_subject',
        labelKey: 'components.assessment_list.class_label',
        render: (item) => {
            const assessment = item as Assessment;
            return (
                <div>
                    <div className="font-medium text-gray-900">
                        {assessment.class_subject?.class?.display_name ??
                            assessment.class_subject?.class?.name ??
                            '-'}
                    </div>
                    <div className="text-sm text-gray-500">
                        {t('student_assessment_pages.index.subject')}:{' '}
                        {assessment.class_subject?.subject?.name || '-'}
                    </div>
                </div>
            );
        },
        conditional: (cv) => (cv === 'teacher' || cv === 'admin') && showClassColumn,
    };

    const teacherColumn: ColumnConfig<AssessmentItem> = {
        key: 'teacher_name',
        labelKey: 'components.assessment_list.teacher_label',
        render: (item) => {
            const assessment = item as Assessment;
            return (
                <span className="text-sm text-gray-700">
                    {assessment.class_subject?.teacher?.name || assessment.teacher?.name || '-'}
                </span>
            );
        },
        conditional: (cv) => cv === 'admin',
    };

    const publishedColumn: ColumnConfig<AssessmentItem> = {
        key: 'is_published',
        labelKey: 'components.assessment_list.status_label',
        render: (item) => {
            const assessment = item as Assessment;
            return (
                <div className="flex items-center space-x-2">
                    <Badge
                        label={
                            assessment.is_published
                                ? t('components.assessment_list.status_published')
                                : t('components.assessment_list.status_unpublished')
                        }
                        type={assessment.is_published ? 'success' : 'gray'}
                        size="sm"
                    />
                    {variant === 'teacher' && (
                        <Toggle
                            checked={assessment.is_published}
                            onChange={() =>
                                handleToggleStatus(assessment.id, assessment.is_published)
                            }
                        />
                    )}
                </div>
            );
        },
        conditional: (cv) => !isAssignmentVariant(cv),
    };

    const createdAtColumn: ColumnConfig<AssessmentItem> = {
        key: 'created_at',
        labelKey: 'components.assessment_list.created_on',
        render: (item) => {
            const assessment = item as Assessment;
            return (
                <span className="text-sm text-gray-500">
                    {formatDate(assessment.created_at, 'datetime')}
                </span>
            );
        },
        conditional: (cv) => !isAssignmentVariant(cv),
    };

    return [
        titleColumn,
        subjectColumn,
        dateColumn,
        statusColumn,
        scoreColumn,
        durationColumn,
        classSubjectColumn,
        teacherColumn,
        publishedColumn,
        createdAtColumn,
    ];
}

interface ActionDeps {
    onView?: AssessmentListProps['onView'];
    enrollment?: Enrollment;
}

function buildActions(deps: ActionDeps): ActionConfig<AssessmentItem>[] {
    const { onView } = deps;

    return [
        {
            labelKey: 'components.assessment_list.view_assessment',
            onClick: (item) => {
                const assessment = item as Assessment;
                if (onView) {
                    onView(assessment);
                }
            },
            color: 'secondary',
            variant: 'outline',
            conditional: (_item, cv) => !isAssignmentVariant(cv),
        },
        {
            labelKey: 'admin_pages.enrollments.view_details',
            onClick: (item) => {
                const assignment = item as AssessmentAssignment;
                router.visit(
                    route('admin.assessments.review', {
                        assessment: assignment.assessment_id,
                        assignment: assignment.id,
                    }),
                );
            },
            color: 'secondary',
            variant: 'outline',
            conditional: (item, cv) => {
                if (cv !== 'class-assignment') return false;
                const assignment = item as AssessmentAssignment;
                return !!assignment.id && assignment.status !== 'submitted';
            },
        },
        {
            labelKey: 'admin_pages.assessments.grade',
            onClick: (item) => {
                const assignment = item as AssessmentAssignment;
                router.visit(
                    route('admin.assessments.grade', {
                        assessment: assignment.assessment_id,
                        assignment: assignment.id,
                    }),
                );
            },
            color: 'primary',
            variant: 'solid',
            conditional: (item, cv) => {
                if (cv !== 'class-assignment') return false;
                const assignment = item as AssessmentAssignment;
                return !!assignment.id && assignment.status === 'submitted';
            },
        },
        {
            labelKey: 'student_assessment_pages.index.view',
            onClick: (item) => {
                const assignment = item as AssignmentWithAssessment;
                router.visit(route('student.assessments.show', assignment.assessment.id));
            },
            color: 'primary',
            variant: 'outline',
            conditional: (item, cv) => {
                if (cv !== 'student') return false;
                const assignment = item as AssignmentWithAssessment;
                return assignment.status === 'not_submitted' || assignment.status === 'in_progress';
            },
        },
        {
            labelKey: 'student_assessment_pages.index.view_results',
            onClick: (item) => {
                const assignment = item as AssignmentWithAssessment;
                router.visit(route('student.assessments.results', assignment.assessment.id));
            },
            color: 'secondary',
            variant: 'outline',
            conditional: (item, cv) => {
                if (cv !== 'student') return false;
                const assignment = item as AssignmentWithAssessment;
                return assignment.status === 'submitted' || assignment.status === 'graded';
            },
        },
    ];
}

function buildAssignmentFilters(
    variant: AssessmentListProps['variant'],
    subjects: ClassSubjectOption[],
    t: TranslateFn,
): FilterConfig[] | undefined {
    if (!isAssignmentVariant(variant)) return undefined;

    return [
        {
            key: 'class_subject_id',
            labelKey: 'student_assessment_pages.index.subject',
            type: 'select',
            options: [
                { value: '', label: t('admin_pages.enrollments.all_subjects') },
                ...subjects.map((s) => ({ value: s.id, label: s.subject_name })),
            ],
        },
        {
            key: 'status',
            labelKey: 'student_assessment_pages.index.status',
            type: 'select',
            options: [
                { value: '', label: t('admin_pages.enrollments.all_assignment_statuses') },
                { value: 'graded', label: t('student_assessment_pages.index.graded') },
                { value: 'submitted', label: t('student_assessment_pages.index.completed') },
                { value: 'in_progress', label: t('student_assessment_pages.index.in_progress') },
                { value: 'not_submitted', label: t('student_assessment_pages.index.not_started') },
            ],
        },
    ];
}

function buildAdminFilters(
    filterSubjects: SimpleFilterOption[],
    filterTeachers: SimpleFilterOption[],
    t: TranslateFn,
): FilterConfig[] {
    const filters: FilterConfig[] = [];

    if (filterSubjects.length > 0) {
        filters.push({
            key: 'subject_id',
            labelKey: 'components.assessment_list.subject_label',
            type: 'select',
            options: [
                { value: '', label: t('components.assessment_list.all_subjects') },
                ...filterSubjects.map((s) => ({ value: s.id, label: s.name })),
            ],
        });
    }

    if (filterTeachers.length > 0) {
        filters.push({
            key: 'teacher_id',
            labelKey: 'components.assessment_list.teacher_label',
            type: 'select',
            options: [
                { value: '', label: t('components.assessment_list.all_teachers') },
                ...filterTeachers.map((t) => ({ value: t.id, label: t.name })),
            ],
        });
    }

    return filters;
}

function buildTeacherFilters(classes: ClassOption[], t: TranslateFn): FilterConfig[] {
    return [
        {
            key: 'class_id',
            labelKey: 'components.assessment_list.class_label',
            type: 'select',
            options: [
                { value: '', label: t('components.assessment_list.all_classes') },
                ...classes.map((c) => ({ value: c.id, label: c.name })),
            ],
        },
    ];
}

/**
 * Unified AssessmentList component for all roles
 *
 * Supports four variants:
 * - admin/teacher: Shows assessments with title, duration, publish toggle, actions
 * - student: Shows assignments with title, subject, class, teacher, due date, status
 * - class-assignment: Admin drill-down showing assignments with score, status, view details
 */
export function AssessmentList({
    data,
    variant = 'teacher',
    onView,
    showPagination = true,
    showClassColumn = true,
    subjects = [],
    classes = [],
    enrollment,
    filterSubjects = [],
    filterTeachers = [],
}: AssessmentListProps) {
    const { t } = useTranslations();
    const { formatDuration } = useFormatters();
    const [, setTogglingAssessments] = useState<Set<number>>(new Set());

    const handleToggleStatus = useCallback((assessmentId: number, isPublished: boolean) => {
        setTogglingAssessments((prev) => {
            if (prev.has(assessmentId)) return prev;
            const next = new Set(prev);
            next.add(assessmentId);
            return next;
        });

        const routeName = isPublished
            ? 'teacher.assessments.unpublish'
            : 'teacher.assessments.publish';

        router.post(
            route(routeName, assessmentId),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setTogglingAssessments((prev) => {
                        const next = new Set(prev);
                        next.delete(assessmentId);
                        return next;
                    });
                },
            },
        );
    }, []);

    const config = useMemo(() => {
        const columns = buildColumns({
            t,
            variant,
            showClassColumn,
            formatDuration,
            handleToggleStatus,
        });
        const actions = buildActions({ onView, enrollment });
        const filters = isAssignmentVariant(variant)
            ? buildAssignmentFilters(variant, subjects, t)
            : variant === 'admin' && (filterSubjects.length > 0 || filterTeachers.length > 0)
              ? buildAdminFilters(filterSubjects, filterTeachers, t)
              : variant === 'teacher' && classes.length > 0
                ? buildTeacherFilters(classes, t)
                : undefined;

        return {
            entity: 'assessment',
            columns,
            actions,
            ...(filters && { filters }),
        };
    }, [
        variant,
        showClassColumn,
        onView,
        handleToggleStatus,
        formatDuration,
        t,
        enrollment,
        subjects,
        classes,
        filterSubjects,
        filterTeachers,
    ]);

    return (
        <BaseEntityList
            data={data}
            config={config}
            variant={variant}
            showPagination={showPagination}
        />
    );
}
