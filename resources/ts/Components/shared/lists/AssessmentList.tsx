import { useState, useMemo, useCallback, type ReactNode } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type Assessment, type AssessmentAssignment } from '@/types';
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

const ASSIGNMENT_VARIANTS: EntityListVariant[] = ['student', 'class-assignment'];

/**
 * Builds the column definitions for all assessment list variants.
 *
 * Each column declares its `variants` whitelist so BaseEntityList
 * can filter declaratively instead of evaluating callbacks at render time.
 * The `class_subject` column retains `conditional` because visibility
 * also depends on the `showClassColumn` prop.
 */
function buildColumns(
    t: TranslateFn,
    showClassColumn: boolean,
    formatDuration: (minutes: number) => string,
    handleToggleStatus: (id: number, isPublished: boolean) => void,
): ColumnConfig<AssessmentItem>[] {
    return [
        {
            key: 'title',
            labelKey: 'components.assessment_list.assessment_label',
            render: (item, variant) => {
                if (isAssignmentVariant(variant)) {
                    const a = item as AssignmentWithAssessment;
                    return (
                        <div>
                            <span className="font-medium text-gray-900">{a.assessment.title}</span>
                            <div className="flex items-center gap-2">
                                {getDeliveryModeBadge(a.assessment.delivery_mode, t)}
                                {variant === 'student' && (
                                    <span className="text-sm text-gray-500">
                                        {' '}
                                        - {a.assessment.questions_count || 0}{' '}
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
        },
        {
            key: 'subject',
            labelKey: 'student_assessment_pages.index.subject',
            conditional: (cv) => isAssignmentVariant(cv) || showClassColumn,
            render: (item, variant) => {
                if (isAssignmentVariant(variant)) {
                    const a = item as AssignmentWithAssessment;
                    return (
                        <div>
                            <span className="text-sm font-medium">
                                {a.assessment.class_subject?.subject?.name || '-'}
                            </span>
                            <div className="text-sm text-gray-500 truncate max-w-sm line-clamp-2">
                                {t('student_assessment_pages.index.teacher')}:{' '}
                                {a.assessment.class_subject?.teacher?.name || '-'}
                            </div>
                        </div>
                    );
                }
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
        },
        {
            key: 'assessment_date',
            labelKey: 'student_assessment_pages.index.assessment_date',
            variants: ASSIGNMENT_VARIANTS,
            render: (item) => {
                const a = item as AssignmentWithAssessment;
                const isHomework =
                    a.assessment.delivery_mode === 'homework' && a.assessment.due_date;
                const dateValue = isHomework ? a.assessment.due_date : a.assessment.scheduled_at;
                const dateLabel = isHomework
                    ? t('student_assessment_pages.show.due_date')
                    : t('student_assessment_pages.show.scheduled_date');
                return (
                    <div>
                        <div className="text-xs text-gray-500">{dateLabel}</div>
                        <span className="text-gray-700">
                            {formatDate(dateValue ?? '', 'datetime')}
                        </span>
                    </div>
                );
            },
        },
        {
            key: 'status',
            labelKey: 'components.assessment_list.status_label',
            render: (item, variant) => {
                if (isAssignmentVariant(variant)) {
                    return getStatusBadge((item as AssignmentWithAssessment).status, t);
                }
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
        },
        {
            key: 'score',
            labelKey: 'admin_pages.enrollments.score',
            variants: ['class-assignment'],
            render: (item) => {
                const a = item as AssignmentWithAssessment;
                if (a.score === null || a.score === undefined) {
                    return <span className="text-gray-400">-</span>;
                }
                const maxPoints =
                    a.assessment?.questions?.reduce(
                        (sum: number, q: { points: number }) => sum + q.points,
                        0,
                    ) ?? 0;
                const percentage =
                    maxPoints > 0 ? Math.round((Number(a.score) / maxPoints) * 100) : 0;
                return (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {a.score} / {maxPoints}
                        </div>
                        <div className="text-xs text-gray-500">{percentage}%</div>
                    </div>
                );
            },
        },
        {
            key: 'duration',
            labelKey: 'components.assessment_list.duration_label',
            variants: ASSIGNMENT_VARIANTS,
            render: (item) => {
                const minutes = (item as AssessmentAssignment).assessment?.duration_minutes;
                return (
                    <span className="text-sm text-gray-900">{formatDuration(minutes || 0)}</span>
                );
            },
        },

        {
            key: 'teacher_name',
            labelKey: 'components.assessment_list.teacher_label',
            variants: ['admin'],
            render: (item) => {
                const assessment = item as Assessment;
                return (
                    <span className="text-sm text-gray-700">
                        {assessment.class_subject?.teacher?.name || assessment.teacher?.name || '-'}
                    </span>
                );
            },
        },

        {
            key: 'created_at',
            labelKey: 'components.assessment_list.created_on',
            variants: ['teacher', 'admin'],
            render: (item) => (
                <span className="text-sm text-gray-500">
                    {formatDate((item as Assessment).created_at, 'datetime')}
                </span>
            ),
        },
    ];
}

/**
 * Builds actions for all assessment list variants.
 */
function buildActions(onView: AssessmentListProps['onView']): ActionConfig<AssessmentItem>[] {
    return [
        {
            labelKey: 'components.assessment_list.view_assessment',
            onClick: (item) => onView?.(item as Assessment),
            color: 'secondary',
            variant: 'outline',
            conditional: (_item, cv) => !isAssignmentVariant(cv),
        },
        {
            labelKey: 'admin_pages.enrollments.view_details',
            onClick: (item) => {
                const a = item as AssessmentAssignment;
                router.visit(
                    route('admin.assessments.review', {
                        assessment: a.assessment_id,
                        assignment: a.id,
                    }),
                );
            },
            color: 'secondary',
            variant: 'outline',
            conditional: (item, cv) => {
                if (cv !== 'class-assignment') return false;
                const a = item as AssessmentAssignment;
                return !!a.id && a.status !== 'submitted';
            },
        },
        {
            labelKey: 'admin_pages.assessments.grade',
            onClick: (item) => {
                const a = item as AssessmentAssignment;
                router.visit(
                    route('admin.assessments.grade', {
                        assessment: a.assessment_id,
                        assignment: a.id,
                    }),
                );
            },
            color: 'primary',
            variant: 'solid',
            conditional: (item, cv) => {
                if (cv !== 'class-assignment') return false;
                const a = item as AssessmentAssignment;
                return !!a.id && a.status === 'submitted';
            },
        },
        {
            labelKey: 'student_assessment_pages.index.view',
            onClick: (item) => {
                const a = item as AssignmentWithAssessment;
                router.visit(route('student.assessments.show', a.assessment.id));
            },
            color: 'primary',
            variant: 'outline',
            conditional: (item, cv) => {
                if (cv !== 'student') return false;
                const a = item as AssignmentWithAssessment;
                return a.status === 'not_submitted' || a.status === 'in_progress';
            },
        },
        {
            labelKey: 'student_assessment_pages.index.view_result',
            onClick: (item) => {
                const a = item as AssignmentWithAssessment;
                router.visit(route('student.assessments.result', a.assessment.id));
            },
            color: 'secondary',
            variant: 'outline',
            conditional: (item, cv) => {
                if (cv !== 'student') return false;
                const a = item as AssignmentWithAssessment;
                return a.status === 'submitted' || a.status === 'graded';
            },
        },
    ];
}

/**
 * Builds filters for the active variant.
 * Assignment variants get subject + status filters.
 * Admin gets subject + teacher filters. Teacher gets class filter.
 */
function buildFilters(
    variant: AssessmentListProps['variant'],
    subjects: ClassSubjectOption[],
    classes: ClassOption[],
    filterSubjects: SimpleFilterOption[],
    filterTeachers: SimpleFilterOption[],
    t: TranslateFn,
): FilterConfig[] | undefined {
    if (variant === 'student' || variant === 'class-assignment') {
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
                    {
                        value: 'in_progress',
                        label: t('student_assessment_pages.index.in_progress'),
                    },
                    {
                        value: 'not_submitted',
                        label: t('student_assessment_pages.index.not_started'),
                    },
                ],
            },
        ];
    }

    if (variant === 'admin') {
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
                    ...filterTeachers.map((teacher) => ({
                        value: teacher.id,
                        label: teacher.name,
                    })),
                ],
            });
        }
        return filters.length > 0 ? filters : undefined;
    }

    if (variant === 'teacher' && classes.length > 0) {
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

    return undefined;
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

    const config = useMemo(
        () => ({
            entity: 'assessment',
            columns: buildColumns(t, showClassColumn, formatDuration, handleToggleStatus),
            actions: buildActions(onView),
            filters: buildFilters(variant, subjects, classes, filterSubjects, filterTeachers, t),
        }),
        [
            variant,
            showClassColumn,
            onView,
            handleToggleStatus,
            formatDuration,
            t,
            subjects,
            classes,
            filterSubjects,
            filterTeachers,
        ],
    );

    return (
        <BaseEntityList
            data={data}
            config={config}
            variant={variant}
            showPagination={showPagination}
        />
    );
}
