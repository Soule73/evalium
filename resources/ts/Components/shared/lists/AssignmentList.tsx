import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { Assessment, AssessmentAssignment } from '@/types';
import { Badge, Button } from '@examena/ui';
import { formatDate, trans } from '@/utils';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface AssignmentWithVirtual extends AssessmentAssignment {
  is_virtual?: boolean;
}

interface AssignmentListProps {
  data: PaginationType<AssignmentWithVirtual>;
  assessment: Assessment;
  totalPoints: number;
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
  onGrade,
  onViewResult,
}: AssignmentListProps) {

  const getStatusBadge = (assignment: AssignmentWithVirtual): { label: string; type: 'gray' | 'info' | 'warning' | 'success' } => {
    if (assignment.is_virtual) {
      return { label: trans('components.assignment_list.status_not_started'), type: 'gray' };
    }
    if (!assignment.submitted_at) {
      return { label: trans('components.assignment_list.status_in_progress'), type: 'info' };
    }
    if (assignment.score === null || assignment.score === undefined) {
      return { label: trans('components.assignment_list.status_pending_grading'), type: 'warning' };
    }
    return { label: trans('components.assignment_list.status_graded'), type: 'success' };
  };

  const handleGradeStudent = (assignment: AssignmentWithVirtual) => {
    if (!assignment.id || !assignment.submitted_at || assignment.is_virtual) return;

    if (onGrade) {
      onGrade(assignment);
    } else {
      router.visit(route('teacher.assessments.grade', {
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
      router.visit(route('teacher.assessments.review', {
        assessment: assessment.id,
        assignment: assignment.id,
      }));
    }
  };

  const config: EntityListConfig<AssignmentWithVirtual> = {
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
            {assignment.submitted_at && !assignment.is_virtual && (
              <>
                {(assignment.score === null || assignment.score === undefined) ? (
                  <Button size="sm" variant="solid" color="primary" onClick={() => handleGradeStudent(assignment)}>
                    {trans('components.assignment_list.grade')}
                  </Button>
                ) : (
                  <>
                    <Button size="sm" variant="outline" color="secondary" onClick={() => handleGradeStudent(assignment)}>
                      {trans('components.assignment_list.edit_grade')}
                    </Button>
                    <Button size="sm" variant="outline" color="secondary" onClick={() => handleViewResult(assignment)}>
                      {trans('components.assignment_list.view_result')}
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
  };

  return (
    <BaseEntityList
      data={data}
      config={config}
      variant="teacher"
      searchPlaceholder={trans('components.assignment_list.search_students')}
      emptyMessage={trans('components.assignment_list.no_students')}
    />
  );
}
