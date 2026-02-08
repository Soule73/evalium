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
  data: PaginationType<Assessment | (AssessmentAssignment & { assessment: Assessment })>;
  variant?: 'admin' | 'teacher' | 'student';
  onView?: (item: Assessment | AssessmentAssignment) => void;
  onEdit?: (item: Assessment | AssessmentAssignment) => void;
  onDelete?: (item: Assessment | AssessmentAssignment) => void;
  onViewDetails?: (item: AssessmentAssignment & { assessment: Assessment }) => void;
  onViewResults?: (item: AssessmentAssignment & { assessment: Assessment }) => void;
}

/**
 * Unified AssessmentList component for all roles (admin, teacher, student)
 *
 * Supports three variants:
 * - admin/teacher: Shows assessments with title, duration, publish toggle, actions
 * - student: Shows assignments with title, subject, class, teacher, due date, status
 */
export function AssessmentList({
  data,
  variant = 'teacher',
  onView,
  onEdit,
  onDelete,
  onViewDetails,
  onViewResults,
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

  const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { label: string; type: 'warning' | 'info' | 'success' | 'gray' }> = {
      not_submitted: { label: 'student_assessment_pages.index.not_started', type: 'warning' },
      submitted: { label: 'student_assessment_pages.index.completed', type: 'info' },
      graded: { label: 'student_assessment_pages.index.graded', type: 'success' },
    };

    const config = statusMap[status] || { label: status, type: 'gray' as const };
    return <Badge label={config.label} type={config.type} />;
  };

  type AssessmentItem = Assessment | (AssessmentAssignment & { assessment: Assessment });

  const config: EntityListConfig<AssessmentItem> = {
    entity: 'assessment',

    columns: [
      {
        key: 'title',
        labelKey: variant === 'student' ? 'student_assessment_pages.index.title' : 'components.assessment_list.assessment_label',
        render: (item: AssessmentItem, currentVariant) => {
          if (currentVariant === 'student') {
            const assignment = item as AssessmentAssignment & { assessment: Assessment };
            return (
              <div>
                <div className="font-medium text-gray-900">{assignment.assessment.title}</div>
                <div className="text-sm text-gray-500">
                  <ClockIcon className="inline w-4 h-4 mr-1" />
                  {formatDuration(assignment.assessment.duration_minutes)} -{' '}
                  {assignment.assessment.questions_count || 0} questions
                </div>
              </div>
            );
          }

          const assessment = item as Assessment;
          return (
            <div>
              <div className="text-sm font-medium text-gray-900">{assessment.title}</div>
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
        render: (item: AssessmentItem) => {
          const assignment = item as AssessmentAssignment & { assessment: Assessment };
          return (
            <span className="text-gray-700">
              {assignment.assessment.class_subject?.subject?.name || '-'}
            </span>
          );
        },
        conditional: (currentVariant) => currentVariant === 'student',
      },

      {
        key: 'class',
        labelKey: 'student_assessment_pages.index.class',
        render: (item: AssessmentItem) => {
          const assignment = item as AssessmentAssignment & { assessment: Assessment };
          return (
            <span className="text-gray-700">
              {assignment.assessment.class_subject?.class?.name || '-'}
            </span>
          );
        },
        conditional: (currentVariant) => currentVariant === 'student',
      },

      {
        key: 'teacher',
        labelKey: 'student_assessment_pages.index.teacher',
        render: (item: AssessmentItem) => {
          const assignment = item as AssessmentAssignment & { assessment: Assessment };
          return (
            <span className="text-gray-700">
              {assignment.assessment.class_subject?.teacher?.name || '-'}
            </span>
          );
        },
        conditional: (currentVariant) => currentVariant === 'student',
      },

      {
        key: 'assessment_date',
        labelKey: 'student_assessment_pages.index.due_date',
        render: (item: AssessmentItem) => {
          const assignment = item as AssessmentAssignment & { assessment: Assessment };
          return (
            <span className="text-gray-700">
              {formatDate(assignment.assessment.scheduled_at)}
            </span>
          );
        },
        conditional: (currentVariant) => currentVariant === 'student',
      },

      {
        key: 'status',
        labelKey: 'student_assessment_pages.index.status',
        render: (item: AssessmentItem) => {
          const assignment = item as AssessmentAssignment & { assessment: Assessment };
          return getStatusBadge(assignment.status);
        },
        conditional: (currentVariant) => currentVariant === 'student',
      },

      {
        key: 'duration',
        labelKey: 'components.assessment_list.duration_label',
        render: (item: AssessmentItem) => {
          const assessment = item as Assessment;
          return (
            <span className="text-sm text-gray-900">
              {formatDuration(assessment.duration_minutes)}
            </span>
          );
        },
        conditional: (currentVariant) => currentVariant !== 'student',
      },

      {
        key: 'class',
        labelKey: 'components.assessment_list.class_label',
        render: (item: AssessmentItem) => {
          const assessment = item as Assessment;
          return (
            <span className="text-sm text-gray-700">
              {assessment.class_subject?.class?.name || '-'}
            </span>
          );
        },
        conditional: (currentVariant) => currentVariant === 'teacher',
      },

      {
        key: 'is_published',
        labelKey: 'components.assessment_list.status_label',
        render: (item: AssessmentItem) => {
          const assessment = item as Assessment;
          return (
            <div className="flex items-center space-x-2">
              <Toggle
                checked={assessment.is_published}
                onChange={() => handleToggleStatus(assessment.id, assessment.is_published)}
              />
            </div>
          );
        },
        conditional: (currentVariant) => currentVariant !== 'student',
      },

      {
        key: 'created_at',
        labelKey: 'components.assessment_list.created_on',
        render: (item: AssessmentItem) => {
          const assessment = item as Assessment;
          return (
            <span className="text-sm text-gray-500">
              {formatDate(assessment.created_at, 'datetime')}
            </span>
          );
        },
        conditional: (currentVariant) => currentVariant !== 'student',
      },
    ],

    actions: [
      {
        labelKey: 'student_assessment_pages.index.view_results',
        onClick: (item) => {
          const assignment = item as AssessmentAssignment & { assessment: Assessment };
          return onViewResults?.(assignment) ||
            router.visit(route('student.assessments.results', assignment.assessment_id));
        },
        color: 'secondary',
        variant: 'outline',
        conditional: (item, currentVariant) => {
          if (currentVariant !== 'student') return false;
          const assignment = item as AssessmentAssignment;
          return assignment.status === 'graded' || assignment.status === 'submitted';
        },
      },
      {
        labelKey: 'student_assessment_pages.index.view_details',
        onClick: (item) => {
          const assignment = item as AssessmentAssignment & { assessment: Assessment };
          return onViewDetails?.(assignment) ||
            router.visit(route('student.assessments.show', assignment.assessment_id));
        },
        color: 'primary',
        variant: 'solid',
        conditional: (item, currentVariant) => {
          if (currentVariant !== 'student') return false;
          const assignment = item as AssessmentAssignment;
          return assignment.status !== 'graded' && assignment.status !== 'submitted';
        },
      },
      {
        labelKey: 'components.assessment_list.view_assessment',
        onClick: (item) => {
          const assessment = item as Assessment;
          return onView?.(assessment) || router.visit(route('teacher.assessments.show', assessment.id));
        },
        permission: 'view assessments',
        color: 'secondary',
        variant: 'outline',
        conditional: (_item, currentVariant) => currentVariant !== 'student',
      },
      {
        labelKey: 'admin_pages.common.edit',
        onClick: (item) => {
          const assessment = item as Assessment;
          return onEdit?.(assessment) || router.visit(route('teacher.assessments.edit', assessment.id));
        },
        permission: 'update assessments',
        color: 'primary',
        variant: 'outline',
        conditional: (_item, currentVariant) => currentVariant === 'teacher',
      },
      {
        labelKey: 'admin_pages.common.delete',
        onClick: (item) => {
          const assessment = item as Assessment;
          return onDelete?.(assessment);
        },
        permission: 'delete assessments',
        color: 'danger',
        variant: 'outline',
        conditional: (_item, currentVariant) => currentVariant === 'teacher',
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant={variant} />;
}
