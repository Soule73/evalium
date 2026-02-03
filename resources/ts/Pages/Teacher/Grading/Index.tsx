import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Assessment, AssessmentAssignment } from '@/types';
import { PaginationType } from '@/types/datatable';
import { Button, DataTable, Section } from '@/Components';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { DataTableConfig } from '@/types/datatable';
import { Badge } from '@examena/ui';
import { formatDate } from '@/utils';

interface Props {
  assessment: Assessment;
  assignments: PaginationType<AssessmentAssignment>;
}

const GradingIndex: React.FC<Props> = ({ assessment, assignments }) => {
  const renderStudentName = (assignment: AssessmentAssignment) => (
    <div>
      <div className="text-sm font-medium text-gray-900">{assignment.student?.name}</div>
      <div className="text-sm text-gray-500">{assignment.student?.email}</div>
    </div>
  );

  const renderStatus = (assignment: AssessmentAssignment) => {
    if (!assignment.submitted_at) {
      return <Badge label={trans('grading_pages.index.not_submitted')} type="gray" />;
    }
    if (assignment.score !== null && assignment.score !== undefined) {
      return <Badge label={trans('grading_pages.index.graded')} type="success" />;
    }
    return <Badge label={trans('grading_pages.index.pending')} type="warning" />;
  };

  const renderScore = (assignment: AssessmentAssignment) => {
    if (assignment.score !== null && assignment.score !== undefined) {
      const totalPoints = (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0);
      const percentage = totalPoints > 0 ? Math.round((assignment.score / totalPoints) * 100) : 0;
      return (
        <div>
          <div className="text-sm font-medium text-gray-900">{assignment.score} / {totalPoints}</div>
          <div className="text-xs text-gray-500">{percentage}%</div>
        </div>
      );
    }
    return <span className="text-gray-400">-</span>;
  };

  const renderSubmittedAt = (assignment: AssessmentAssignment) => {
    if (assignment.submitted_at) {
      return <span className="text-sm text-gray-900">{formatDate(assignment.submitted_at, 'datetime')}</span>;
    }
    return <span className="text-gray-400">-</span>;
  };

  const renderActions = (assignment: AssessmentAssignment) => (
    <div className="flex items-center justify-end space-x-2">
      {assignment.submitted_at && (
        <Button
          size="sm"
          onClick={() => router.visit(route('teacher.grading.show', {
            assessment: assessment.id,
            student: assignment.student_id
          }))}
          variant="outline"
        >
          {assignment.score !== null ? trans('grading_pages.index.review') : trans('grading_pages.index.grade')}
        </Button>
      )}
    </div>
  );

  const columns: DataTableConfig<AssessmentAssignment>["columns"] = [
    { key: 'student', label: trans('grading_pages.index.student_label'), render: renderStudentName },
    { key: 'status', label: trans('grading_pages.index.status_label'), render: renderStatus },
    { key: 'score', label: trans('grading_pages.index.score_label'), render: renderScore },
    { key: 'submitted_at', label: trans('grading_pages.index.submitted_at'), render: renderSubmittedAt },
    { key: 'actions', label: trans('grading_pages.index.actions_label'), className: 'text-right', render: renderActions },
  ];

  const emptyState = {
    title: trans('grading_pages.index.empty_title'),
    subtitle: trans('grading_pages.index.empty_subtitle')
  };

  const tableConfig: DataTableConfig<AssessmentAssignment> = {
    columns,
    emptyState,
    perPageOptions: [10, 25, 50]
  };

  return (
    <AuthenticatedLayout
      title={trans('grading_pages.index.title', { assessment: assessment.title })}
      breadcrumb={breadcrumbs.showTeacherAssessment(assessment)}
    >
      <div className="max-w-6xl mx-auto space-y-6">
        <Section
          title={trans('grading_pages.index.section_title')}
          subtitle={trans('grading_pages.index.section_subtitle', { count: assignments.total })}
          actions={
            <Button
              onClick={() => router.visit(route('teacher.assessments.show', assessment.id))}
              variant="outline"
              size="sm"
            >
              {trans('grading_pages.index.back_to_assessment')}
            </Button>
          }
        >
          <DataTable data={assignments} config={tableConfig} />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
};

export default GradingIndex;
