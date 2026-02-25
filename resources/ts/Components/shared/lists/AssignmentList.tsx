import { useState, useCallback, useMemo, memo } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import {
    type Assessment,
    type AssessmentAssignment,
    type AssessmentRouteContext,
    type PageProps,
} from '@/types';
import { Badge, Button } from '@evalium/ui';
import { ConfirmationModal, Textarea } from '@/Components';
import { formatDate, hasPermission } from '@/utils';
import { useTranslations } from '@/hooks';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';
import { ArrowPathIcon } from '@heroicons/react/24/outline';

interface AssignmentWithVirtual extends AssessmentAssignment {
    is_virtual?: boolean;
}

interface AssignmentListProps {
    data: PaginationType<AssignmentWithVirtual>;
    assessment: Assessment;
    totalPoints: number;
    routeContext?: AssessmentRouteContext;
    onGrade?: (assignment: AssignmentWithVirtual) => void;
    onViewResult?: (assignment: AssignmentWithVirtual) => void;
}

type StatusResult = { label: string; type: 'gray' | 'info' | 'warning' | 'success' };

function resolveStatus(assignment: AssignmentWithVirtual, t: (k: string) => string): StatusResult {
    if (assignment.is_virtual) {
        return { label: t('components.assignment_list.status_not_started'), type: 'gray' };
    }
    if (!assignment.submitted_at) {
        return { label: t('components.assignment_list.status_in_progress'), type: 'info' };
    }
    if (assignment.score === null || assignment.score === undefined) {
        return { label: t('components.assignment_list.status_pending_grading'), type: 'warning' };
    }
    return { label: t('components.assignment_list.status_graded'), type: 'success' };
}

function canReopen(
    assignment: AssignmentWithVirtual,
    isSupervisedMode: boolean,
    hasReopenRoute: boolean,
): boolean {
    if (!hasReopenRoute || !isSupervisedMode) return false;
    if (assignment.is_virtual || !assignment.started_at || !assignment.submitted_at) return false;
    return !!(assignment.forced_submission || assignment.security_violation);
}

const StudentCell = memo(({ assignment }: { assignment: AssignmentWithVirtual }) => (
    <div>
        <div className="font-medium text-gray-900">{assignment.student?.name}</div>
        <div className="text-sm text-gray-500">{assignment.student?.email}</div>
    </div>
));

const ScoreCell = memo(
    ({ score, totalPoints }: { score: number | null | undefined; totalPoints: number }) => {
        if (score === null || score === undefined) {
            return <span className="text-gray-400">-</span>;
        }
        const percentage = totalPoints > 0 ? Math.round((score / totalPoints) * 100) : 0;
        return (
            <div>
                <div className="text-sm font-medium text-gray-900">
                    {score} / {totalPoints}
                </div>
                <div className="text-xs text-gray-500">{percentage}%</div>
            </div>
        );
    },
);

const SubmittedAtCell = memo(({ submittedAt }: { submittedAt: string | null | undefined }) =>
    submittedAt ? (
        <span className="text-sm text-gray-600">{formatDate(submittedAt, 'datetime')}</span>
    ) : (
        <span className="text-gray-400">-</span>
    ),
);

interface AssignmentActionsProps {
    assignment: AssignmentWithVirtual;
    isSupervisedMode: boolean;
    hasReopenRoute: boolean;
    assessmentHasEnded: boolean;
    onReopen: (assignment: AssignmentWithVirtual) => void;
    onGrade: (assignment: AssignmentWithVirtual) => void;
    onView: (assignment: AssignmentWithVirtual) => void;
}

const AssignmentActions = memo(
    ({
        assignment,
        isSupervisedMode,
        hasReopenRoute,
        assessmentHasEnded,
        onReopen,
        onGrade,
        onView,
    }: AssignmentActionsProps) => {
        const { t } = useTranslations();
        const { auth } = usePage<PageProps>().props;
        const canGrade = hasPermission(auth.permissions, 'grade assessments');
        const canView = hasPermission(auth.permissions, 'view assessments');

        const isGradeable =
            !assignment.is_virtual && (!!assignment.submitted_at || assessmentHasEnded);
        const showReopen = canGrade && canReopen(assignment, isSupervisedMode, hasReopenRoute);
        const showGradeActions = canGrade && isGradeable;
        const showView = canView && !assignment.is_virtual && assignment.score;
        const isUngraded = !assignment.score;

        if (!showReopen && !showGradeActions && !showView) return null;

        return (
            <div className="flex items-center justify-start space-x-2">
                {showReopen && (
                    <Button
                        size="sm"
                        variant="outline"
                        color="warning"
                        onClick={() => onReopen(assignment)}
                        title={t('components.assignment_list.allow_retry')}
                    >
                        <ArrowPathIcon className="h-4 w-4 mr-1" />
                        {t('components.assignment_list.allow_retry')}
                    </Button>
                )}
                {showGradeActions && (
                    <Button
                        size="sm"
                        variant={isUngraded ? 'solid' : 'outline'}
                        color={isUngraded ? 'primary' : 'secondary'}
                        onClick={() => onGrade(assignment)}
                    >
                        {t(
                            isUngraded
                                ? 'components.assignment_list.grade'
                                : 'components.assignment_list.edit_grade',
                        )}
                    </Button>
                )}
                {showView && (
                    <Button
                        size="sm"
                        variant="outline"
                        color="secondary"
                        onClick={() => onView(assignment)}
                    >
                        {t('components.assignment_list.view_result')}
                    </Button>
                )}
            </div>
        );
    },
);

function useReopenModal(assessmentId: number, reopenRoute: string, t: (k: string) => string) {
    const [target, setTarget] = useState<AssignmentWithVirtual | null>(null);
    const [reason, setReason] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const open = useCallback((assignment: AssignmentWithVirtual) => {
        setTarget(assignment);
        setReason('');
        setError(null);
    }, []);

    const close = useCallback(() => {
        setTarget(null);
        setReason('');
        setError(null);
    }, []);

    const confirm = useCallback(async () => {
        if (!target || !reason.trim()) return;
        setLoading(true);
        setError(null);
        try {
            await axios.post(
                route(reopenRoute, { assessment: assessmentId, assignment: target.id }),
                { reason },
            );
            close();
            router.visit(window.location.href, { preserveState: true, preserveScroll: true });
        } catch (err: unknown) {
            setError(
                axios.isAxiosError(err) && err.response?.data?.message
                    ? err.response.data.message
                    : t('components.assignment_list.reopen_error'),
            );
        } finally {
            setLoading(false);
        }
    }, [target, reason, assessmentId, reopenRoute, close, t]);

    return { target, reason, setReason, loading, error, open, close, confirm };
}

interface ColumnDeps {
    totalPoints: number;
    isSupervisedMode: boolean;
    hasReopenRoute: boolean;
    assessmentHasEnded: boolean;
    t: (key: string) => string;
    onReopen: (a: AssignmentWithVirtual) => void;
    onGrade: (a: AssignmentWithVirtual) => void;
    onView: (a: AssignmentWithVirtual) => void;
}

function buildColumns(deps: ColumnDeps): EntityListConfig<AssignmentWithVirtual>['columns'] {
    const {
        totalPoints,
        isSupervisedMode,
        hasReopenRoute,
        assessmentHasEnded,
        t,
        onReopen,
        onGrade,
        onView,
    } = deps;
    return [
        {
            key: 'student',
            labelKey: 'components.assignment_list.student',
            render: (a) => <StudentCell assignment={a} />,
        },
        {
            key: 'status',
            labelKey: 'components.assignment_list.status',
            render: (a) => {
                const s = resolveStatus(a, t);
                return <Badge label={s.label} type={s.type} size="sm" />;
            },
        },
        {
            key: 'score',
            labelKey: 'components.assignment_list.score',
            render: (a) => <ScoreCell score={a.score} totalPoints={totalPoints} />,
        },
        {
            key: 'submitted_at',
            labelKey: 'components.assignment_list.submitted_at',
            render: (a) => <SubmittedAtCell submittedAt={a.submitted_at} />,
        },
        {
            key: 'actions',
            labelKey: 'components.assignment_list.actions',
            render: (a) => (
                <AssignmentActions
                    assignment={a}
                    isSupervisedMode={isSupervisedMode}
                    hasReopenRoute={hasReopenRoute}
                    assessmentHasEnded={assessmentHasEnded}
                    onReopen={onReopen}
                    onGrade={onGrade}
                    onView={onView}
                />
            ),
            sortable: false,
        },
    ];
}

/**
 * Displays the list of student assignments for a given assessment.
 *
 * Delegates permission checks to AssignmentActions, status resolution to resolveStatus,
 * and reopen modal state to useReopenModal.
 */
export function AssignmentList({
    data,
    assessment,
    totalPoints,
    routeContext,
    onGrade,
    onViewResult,
}: AssignmentListProps) {
    const { t } = useTranslations();
    const isSupervisedMode = assessment.delivery_mode === 'supervised';
    const hasReopenRoute = !routeContext || !!routeContext.reopenRoute;
    const reopenRouteName = routeContext?.reopenRoute ?? 'teacher.assessments.reopen';
    const reopen = useReopenModal(assessment.id, reopenRouteName, t);

    const handleGrade = useCallback(
        (assignment: AssignmentWithVirtual) => {
            if (!assignment.id || assignment.is_virtual) return;
            if (!assignment.submitted_at && !assessment.has_ended) return;
            if (onGrade) {
                onGrade(assignment);
                return;
            }
            router.visit(
                route(routeContext?.gradeRoute ?? 'teacher.assessments.grade', {
                    assessment: assessment.id,
                    assignment: assignment.id,
                }),
            );
        },
        [onGrade, assessment.id, assessment.has_ended, routeContext?.gradeRoute],
    );

    const handleView = useCallback(
        (assignment: AssignmentWithVirtual) => {
            if (!assignment.id || assignment.is_virtual) return;
            if (onViewResult) {
                onViewResult(assignment);
                return;
            }
            router.visit(
                route(routeContext?.reviewRoute ?? 'teacher.assessments.review', {
                    assessment: assessment.id,
                    assignment: assignment.id,
                }),
            );
        },
        [onViewResult, assessment.id, routeContext?.reviewRoute],
    );

    const config: EntityListConfig<AssignmentWithVirtual> = useMemo(
        () => ({
            entity: 'assignment',
            columns: buildColumns({
                totalPoints,
                isSupervisedMode,
                hasReopenRoute,
                assessmentHasEnded: assessment.has_ended,
                t,
                onReopen: reopen.open,
                onGrade: handleGrade,
                onView: handleView,
            }),
            actions: [],
        }),
        [
            totalPoints,
            isSupervisedMode,
            hasReopenRoute,
            assessment.has_ended,
            t,
            reopen.open,
            handleGrade,
            handleView,
        ],
    );

    return (
        <>
            <BaseEntityList
                data={data}
                config={config}
                variant="teacher"
                searchPlaceholder={t('components.assignment_list.search_students')}
                emptyMessage={t('components.assignment_list.no_students')}
            />
            <ConfirmationModal
                isOpen={!!reopen.target}
                onClose={reopen.close}
                onConfirm={reopen.confirm}
                title={t('components.assignment_list.reopen_modal_title')}
                message={t('components.assignment_list.reopen_modal_message', {
                    student: reopen.target?.student?.name ?? '',
                })}
                confirmText={t('components.assignment_list.reopen_confirm')}
                cancelText={t('commons/ui.cancel')}
                type="warning"
                loading={reopen.loading}
            >
                {reopen.error && <p className="text-sm text-red-600 mb-3">{reopen.error}</p>}
                <Textarea
                    label={t('components.assignment_list.reopen_reason_label')}
                    value={reopen.reason}
                    onChange={(e) => reopen.setReason(e.target.value)}
                    placeholder={t('components.assignment_list.reopen_reason_placeholder')}
                    rows={3}
                />
            </ConfirmationModal>
        </>
    );
}
