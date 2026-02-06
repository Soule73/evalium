import { useState } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { Assessment, AssessmentAssignment } from '@/types';
import { Badge, MarkdownRenderer, Toggle } from '@examena/ui';
import { ClockIcon } from '@heroicons/react/24/outline';
import { formatDate, formatDuration } from '@/utils';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface AssessmentListProps {
  data: PaginationType<Assessment>;
  variant?: 'admin' | 'teacher';
  onView?: (assessment: Assessment) => void;
  onEdit?: (assessment: Assessment) => void;
  onDelete?: (assessment: Assessment) => void;
}

interface StudentAssessmentListProps {
  data: PaginationType<AssessmentAssignment & { assessment: Assessment }>;
  onViewDetails?: (assignment: AssessmentAssignment & { assessment: Assessment }) => void;
  onViewResults?: (assignment: AssessmentAssignment & { assessment: Assessment }) => void;
}

/**
 * AssessmentList component for displaying assessments (teacher/admin view)
 *
 * Shows assessment title, duration, publish status, and actions
 */
export function AssessmentList({
  data,
  variant = 'teacher',
  onView,
  onEdit,
  onDelete,
}: AssessmentListProps) {
  const [togglingAssessments, setTogglingAssessments] = useState<Set<number>>(new Set());

  const handleToggleStatus = (assessmentId: number, isPublished: boolean) => {
    if (togglingAssessments.has(assessmentId)) return;

    setTogglingAssessments((prev) => new Set(prev).add(assessmentId));

    const routeName = isPublished
      ? 'teacher.assessments.unpublish'
      : 'teacher.assessments.publish';

    router.post(route(routeName, assessmentId), {}, {
      preserveScroll: true,
      onFinish: () => {
        setTogglingAssessments((prev) => {
          const next = new Set(prev);
          next.delete(assessmentId);
          return next;
        });
      },
    });
  };

  const config: EntityListConfig<Assessment> = {
    entity: 'assessment',

    columns: [
      {
        key: 'title',
        labelKey: 'components.assessment_list.assessment_label',
        render: (assessment) => (
          <div>
            <div className="text-sm font-medium text-gray-900">{assessment.title}</div>
            {assessment.description && (
              <div className="text-sm text-gray-500 truncate max-w-sm line-clamp-2">
                <MarkdownRenderer>{assessment.description}</MarkdownRenderer>
              </div>
            )}
          </div>
        ),
      },

      {
        key: 'duration',
        labelKey: 'components.assessment_list.duration_label',
        render: (assessment) => (
          <span className="text-sm text-gray-900">
            {formatDuration(assessment.duration_minutes)}
          </span>
        ),
      },

      {
        key: 'is_published',
        labelKey: 'components.assessment_list.status_label',
        render: (assessment) => (
          <div className="flex items-center space-x-2">
            <Toggle
              checked={assessment.is_published}
              onChange={() => handleToggleStatus(assessment.id, assessment.is_published)}
            />
          </div>
        ),
      },

      {
        key: 'created_at',
        labelKey: 'components.assessment_list.created_on',
        render: (assessment) => (
          <span className="text-sm text-gray-500">
            {formatDate(assessment.created_at, 'datetime')}
          </span>
        ),
      },
    ],

    actions: [
      {
        labelKey: 'components.assessment_list.view_assessment',
        onClick: (item) =>
          onView?.(item) || router.visit(route('teacher.assessments.show', item.id)),
        permission: 'view assessments',
        color: 'secondary',
        variant: 'outline',
      },
      {
        labelKey: 'admin_pages.common.edit',
        onClick: (item) =>
          onEdit?.(item) || router.visit(route('teacher.assessments.edit', item.id)),
        permission: 'update assessments',
        color: 'primary',
        variant: 'outline',
        conditional: (_item, currentVariant) => currentVariant === 'teacher',
      },
      {
        labelKey: 'admin_pages.common.delete',
        onClick: (item) => onDelete?.(item),
        permission: 'delete assessments',
        color: 'danger',
        variant: 'outline',
        conditional: (_item, currentVariant) => currentVariant === 'teacher',
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant={variant} />;
}

/**
 * StudentAssessmentList component for displaying student assessment assignments
 *
 * Shows assignment details including subject, class, teacher, due date, and status
 */
export function StudentAssessmentList({
  data,
  onViewDetails,
  onViewResults,
}: StudentAssessmentListProps) {
  const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { label: string; type: 'warning' | 'info' | 'success' | 'gray' }> = {
      not_submitted: { label: 'student_assessment_pages.index.not_started', type: 'warning' },
      submitted: { label: 'student_assessment_pages.index.completed', type: 'info' },
      graded: { label: 'student_assessment_pages.index.graded', type: 'success' },
    };

    const config = statusMap[status] || { label: status, type: 'gray' as const };
    return <Badge label={config.label} type={config.type} />;
  };

  const config: EntityListConfig<AssessmentAssignment & { assessment: Assessment }> = {
    entity: 'assessment_assignment',

    columns: [
      {
        key: 'title',
        labelKey: 'student_assessment_pages.index.title',
        render: (assignment) => (
          <div>
            <div className="font-medium text-gray-900">{assignment.assessment.title}</div>
            <div className="text-sm text-gray-500">
              <ClockIcon className="inline w-4 h-4 mr-1" />
              {formatDuration(assignment.assessment.duration_minutes)} -{' '}
              {assignment.assessment.questions_count || 0} questions
            </div>
          </div>
        ),
      },

      {
        key: 'subject',
        labelKey: 'student_assessment_pages.index.subject',
        render: (assignment) => (
          <span className="text-gray-700">
            {assignment.assessment.class_subject?.subject?.name || '-'}
          </span>
        ),
      },

      {
        key: 'class',
        labelKey: 'student_assessment_pages.index.class',
        render: (assignment) => (
          <span className="text-gray-700">
            {assignment.assessment.class_subject?.class?.name || '-'}
          </span>
        ),
      },

      {
        key: 'teacher',
        labelKey: 'student_assessment_pages.index.teacher',
        render: (assignment) => (
          <span className="text-gray-700">
            {assignment.assessment.class_subject?.teacher?.name || '-'}
          </span>
        ),
      },

      {
        key: 'assessment_date',
        labelKey: 'student_assessment_pages.index.due_date',
        render: (assignment) => (
          <span className="text-gray-700">
            {formatDate(assignment.assessment.scheduled_at)}
          </span>
        ),
      },

      {
        key: 'status',
        labelKey: 'student_assessment_pages.index.status',
        render: (assignment) => getStatusBadge(assignment.status),
      },
    ],

    actions: [
      {
        labelKey: 'student_assessment_pages.index.view_results',
        onClick: (item) =>
          onViewResults?.(item) ||
          router.visit(route('student.assessments.results', item.assessment_id)),
        color: 'secondary',
        variant: 'outline',
        conditional: (item) => item.status === 'graded' || item.status === 'submitted',
      },
      {
        labelKey: 'student_assessment_pages.index.view_details',
        onClick: (item) =>
          onViewDetails?.(item) ||
          router.visit(route('student.assessments.show', item.assessment_id)),
        color: 'primary',
        variant: 'solid',
        conditional: (item) => item.status !== 'graded' && item.status !== 'submitted',
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant="student" />;
}
