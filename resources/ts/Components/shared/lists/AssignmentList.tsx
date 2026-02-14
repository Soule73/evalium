import { useState, useCallback, useMemo } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type Assessment, type AssessmentAssignment, type AssessmentRouteContext } from '@/types';
import { Badge, Button } from '@examena/ui';
import { ConfirmationModal, Textarea } from '@/Components';
import { formatDate } from '@/utils';
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

/**
 * AssignmentList component for displaying student assignments on an assessment
 *
 * Shows:
 * - Student name and email
 * - Status (not started, in progress, pending grading, graded)
 * - Score with percentage
 * - Submission date
 * - Actions (grade, edit grade, view result)
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
  const [reopenTarget, setReopenTarget] = useState<AssignmentWithVirtual | null>(null);
  const [reopenReason, setReopenReason] = useState('');
  const [reopening, setReopening] = useState(false);
  const [reopenError, setReopenError] = useState<string | null>(null);

  const isSupervisedMode = assessment.delivery_mode === 'supervised';

  const hasReopenRoute = !routeContext || routeContext.reopenRoute;

  const canReopenAssignment = useCallback((assignment: AssignmentWithVirtual): boolean => {
    if (!hasReopenRoute) return false;
    if (!isSupervisedMode || assignment.is_virtual || !assignment.started_at) return false;
    if (!assignment.submitted_at) return false;
    if (assignment.forced_submission || assignment.security_violation) return true;
    return false;
  }, [isSupervisedMode, hasReopenRoute]);

  const handleReopenConfirm = useCallback(async () => {
    if (!reopenTarget || !reopenReason.trim()) return;
    setReopening(true);
    setReopenError(null);

    try {
      const reopenRouteName = routeContext?.reopenRoute || 'teacher.assessments.reopen';
      await axios.post(
        route(reopenRouteName, { assessment: assessment.id, assignment: reopenTarget.id }),
        { reason: reopenReason }
      );
      setReopenTarget(null);
      setReopenReason('');
      router.visit(window.location.href, { preserveState: true, preserveScroll: true });
    } catch (err: unknown) {
      if (axios.isAxiosError(err) && err.response?.data?.message) {
        setReopenError(err.response.data.message);
      } else {
        setReopenError(t('components.assignment_list.reopen_error'));
      }
    } finally {
      setReopening(false);
    }
  }, [reopenTarget, reopenReason, assessment.id]);

  const getStatusBadge = (assignment: AssignmentWithVirtual): { label: string; type: 'gray' | 'info' | 'warning' | 'success' } => {
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
  };

  const handleGradeStudent = (assignment: AssignmentWithVirtual) => {
    if (!assignment.id || !assignment.submitted_at || assignment.is_virtual) return;

    if (onGrade) {
      onGrade(assignment);
    } else {
      const gradeRouteName = routeContext?.gradeRoute || 'teacher.assessments.grade';
      router.visit(route(gradeRouteName, {
        assessment: assessment.id,
        assignment: assignment.id,
      }));
    }
  };

  const handleViewResult = (assignment: AssignmentWithVirtual) => {
    if (!assignment.id || assignment.is_virtual) return;

    if (onViewResult) {
      onViewResult(assignment);
    } else {
      const reviewRouteName = routeContext?.reviewRoute || 'teacher.assessments.review';
      router.visit(route(reviewRouteName, {
        assessment: assessment.id,
        assignment: assignment.id,
      }));
    }
  };

  const config: EntityListConfig<AssignmentWithVirtual> = useMemo(() => ({
    entity: 'assignment',

    columns: [
      {
        key: 'student',
        labelKey: 'components.assignment_list.student',
        render: (assignment) => (
          <div>
            <div className="font-medium text-gray-900">{assignment.student?.name}</div>
            <div className="text-sm text-gray-500">{assignment.student?.email}</div>
          </div>
        ),
      },
      {
        key: 'status',
        labelKey: 'components.assignment_list.status',
        render: (assignment) => {
          const status = getStatusBadge(assignment);
          return <Badge label={status.label} type={status.type} size="sm" />;
        },
      },
      {
        key: 'score',
        labelKey: 'components.assignment_list.score',
        render: (assignment) => {
          if (assignment.score !== null && assignment.score !== undefined) {
            const percentage = totalPoints > 0 ? Math.round((assignment.score / totalPoints) * 100) : 0;
            return (
              <div>
                <div className="text-sm font-medium text-gray-900">{assignment.score} / {totalPoints}</div>
                <div className="text-xs text-gray-500">{percentage}%</div>
              </div>
            );
          }
          return <span className="text-gray-400">-</span>;
        },
      },
      {
        key: 'submitted_at',
        labelKey: 'components.assignment_list.submitted_at',
        render: (assignment) => assignment.submitted_at
          ? <span className="text-sm text-gray-600">{formatDate(assignment.submitted_at, 'datetime')}</span>
          : <span className="text-gray-400">-</span>,
      },
      {
        key: 'actions',
        labelKey: 'components.assignment_list.actions',
        render: (assignment) => (
          <div className="flex items-center justify-end space-x-2">
            {canReopenAssignment(assignment) && (
              <Button
                size="sm"
                variant="outline"
                color="warning"
                onClick={() => {
                  setReopenTarget(assignment);
                  setReopenReason('');
                  setReopenError(null);
                }}
                title={t('components.assignment_list.allow_retry')}
              >
                <ArrowPathIcon className="h-4 w-4 mr-1" />
                {t('components.assignment_list.allow_retry')}
              </Button>
            )}
            {assignment.submitted_at && !assignment.is_virtual && (
              <>
                {(assignment.score === null || assignment.score === undefined) ? (
                  <Button size="sm" variant="solid" color="primary" onClick={() => handleGradeStudent(assignment)}>
                    {t('components.assignment_list.grade')}
                  </Button>
                ) : (
                  <>
                    <Button size="sm" variant="outline" color="secondary" onClick={() => handleGradeStudent(assignment)}>
                      {t('components.assignment_list.edit_grade')}
                    </Button>
                    <Button size="sm" variant="outline" color="secondary" onClick={() => handleViewResult(assignment)}>
                      {t('components.assignment_list.view_result')}
                    </Button>
                  </>
                )}
              </>
            )}
          </div>
        ),
        sortable: false,
      },
    ],

    actions: [],
  }), [totalPoints, isSupervisedMode, canReopenAssignment, onGrade, onViewResult, assessment.id, routeContext, t]);

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
        isOpen={!!reopenTarget}
        onClose={() => { setReopenTarget(null); setReopenReason(''); setReopenError(null); }}
        onConfirm={handleReopenConfirm}
        title={t('components.assignment_list.reopen_modal_title')}
        message={t('components.assignment_list.reopen_modal_message', {
          student: reopenTarget?.student?.name ?? '',
        })}
        confirmText={t('components.assignment_list.reopen_confirm')}
        cancelText={t('components.confirmation_modal.cancel')}
        type="warning"
        loading={reopening}
      >
        {reopenError && (
          <p className="text-sm text-red-600 mb-3">{reopenError}</p>
        )}
        <Textarea
          label={t('components.assignment_list.reopen_reason_label')}
          value={reopenReason}
          onChange={(e) => setReopenReason(e.target.value)}
          placeholder={t('components.assignment_list.reopen_reason_placeholder')}
          rows={3}
        />
      </ConfirmationModal>
    </>
  );
}
