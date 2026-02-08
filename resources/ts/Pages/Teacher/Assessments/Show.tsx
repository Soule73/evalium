import React, { useState, useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { formatDuration, formatDate } from '@/utils';
import { Button, ConfirmationModal, Section, Stat, DataTable, Badge } from '@/Components';
import { Toggle } from '@examena/ui';
import { Assessment, AssessmentAssignment } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { ClockIcon, QuestionMarkCircleIcon, StarIcon, DocumentDuplicateIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { breadcrumbs, trans } from '@/utils';
import { QuestionReadOnlySection } from '@/Components';
import { QuestionResultReadOnlyText, QuestionTeacherReadOnlyChoices } from '@/Components/features/assessment/QuestionResultReadOnly';
import { AssessmentHeader } from '@/Components/features/assessment/AssessmentHeader';

interface AssignmentWithVirtual extends AssessmentAssignment {
  is_virtual?: boolean;
}

interface Props {
  assessment: Assessment;
  assignments: PaginationType<AssignmentWithVirtual>;
}

const AssessmentShow: React.FC<Props> = ({ assessment, assignments }) => {
  const [isToggling, setIsToggling] = useState(false);
  const [isDuplicating, setIsDuplicating] = useState(false);
  const [showDuplicateModal, setShowDuplicateModal] = useState(false);

  const totalPoints = useMemo(() =>
    (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0),
    [assessment.questions]
  );

  const totalStudents = assignments.total;
  const questionsCount = (assessment.questions ?? []).length;

  const handleToggleStatus = () => {
    if (isToggling) return;
    setIsToggling(true);
    const routeName = assessment.is_published ? 'teacher.assessments.unpublish' : 'teacher.assessments.publish';
    router.post(route(routeName, assessment.id), {}, {
      preserveScroll: true,
      onFinish: () => setIsToggling(false),
    });
  };

  const handleDuplicate = () => {
    if (isDuplicating) return;
    setIsDuplicating(true);
    router.post(route('teacher.assessments.duplicate', assessment.id), {}, {
      onFinish: () => {
        setIsDuplicating(false);
        setShowDuplicateModal(false);
      },
    });
  };

  const handleGradeStudent = (assignment: AssignmentWithVirtual) => {
    if (!assignment.submitted_at) return;
    router.visit(route('teacher.assessments.grade', {
      assessment: assessment.id,
      assignment: assignment.id,
    }));
  };

  const handleViewResult = (assignment: AssignmentWithVirtual) => {
    router.visit(route('teacher.assessments.review', {
      assessment: assessment.id,
      assignment: assignment.id,
    }));
  };

  const getAssignmentStatus = (assignment: AssignmentWithVirtual): { label: string; type: 'gray' | 'info' | 'warning' | 'success' } => {
    if (assignment.is_virtual) {
      return { label: trans('assessment_pages.show.status_not_started'), type: 'gray' };
    }
    if (!assignment.submitted_at) {
      return { label: trans('assessment_pages.show.status_in_progress'), type: 'info' };
    }
    if (assignment.score === null || assignment.score === undefined) {
      return { label: trans('assessment_pages.show.status_pending_grading'), type: 'warning' };
    }
    return { label: trans('assessment_pages.show.status_graded'), type: 'success' };
  };

  const assignmentsTableConfig: DataTableConfig<AssignmentWithVirtual> = {
    columns: [
      {
        key: 'student',
        label: trans('assessment_pages.show.student'),
        render: (assignment) => (
          <div>
            <div className="font-medium text-gray-900">{assignment.student?.name}</div>
            <div className="text-sm text-gray-500">{assignment.student?.email}</div>
          </div>
        ),
      },
      {
        key: 'status',
        label: trans('assessment_pages.show.status'),
        render: (assignment) => {
          const status = getAssignmentStatus(assignment);
          return <Badge label={status.label} type={status.type} size="sm" />;
        },
      },
      {
        key: 'score',
        label: trans('assessment_pages.show.score'),
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
        label: trans('assessment_pages.show.submitted_at'),
        render: (assignment) => assignment.submitted_at
          ? <span className="text-sm text-gray-600">{formatDate(assignment.submitted_at, 'datetime')}</span>
          : <span className="text-gray-400">-</span>,
      },
      {
        key: 'actions',
        label: trans('assessment_pages.show.actions'),
        className: 'text-right',
        render: (assignment) => (
          <div className="flex items-center justify-end space-x-2">
            {assignment.submitted_at && !assignment.is_virtual && (
              <>
                {(assignment.score === null || assignment.score === undefined) ? (
                  <Button size="sm" variant="solid" color="primary" onClick={() => handleGradeStudent(assignment)}>
                    {trans('assessment_pages.show.grade')}
                  </Button>
                ) : (
                  <>
                    <Button size="sm" variant="outline" color="secondary" onClick={() => handleGradeStudent(assignment)}>
                      {trans('assessment_pages.show.edit_grade')}
                    </Button>
                    <Button size="sm" variant="outline" color="secondary" onClick={() => handleViewResult(assignment)}>
                      {trans('assessment_pages.show.view_result')}
                    </Button>
                  </>
                )}
              </>
            )}
          </div>
        ),
      },
    ],
    searchPlaceholder: trans('assessment_pages.show.search_students'),
    perPageOptions: [10, 25, 50],
    emptyState: {
      title: trans('assessment_pages.show.no_students'),
      subtitle: trans('assessment_pages.show.no_students_description'),
    },
    emptySearchState: {
      title: trans('assessment_pages.show.no_students_found'),
      subtitle: trans('assessment_pages.show.no_students_found_description'),
      resetLabel: trans('assessment_pages.show.reset_search'),
    },
  };

  return (
    <AuthenticatedLayout
      title={assessment.title}
      breadcrumb={breadcrumbs.showTeacherAssessment(assessment)}
    >
      <div className="max-w-6xl mx-auto space-y-6">
        <Section
          title={trans('assessment_pages.common.subtitle')}
          actions={
            <div className="flex flex-col md:flex-row space-y-2 md:space-x-3 md:space-y-0">
              <Toggle
                checked={assessment.is_published}
                onChange={handleToggleStatus}
                disabled={isToggling}
                color="green"
                size="sm"
                showLabel
                activeLabel={trans('assessment_pages.common.toggle_published')}
                inactiveLabel={trans('assessment_pages.common.toggle_unpublished')}
              />
              <Button
                onClick={() => setShowDuplicateModal(true)}
                color="secondary"
                variant='outline'
                size="sm"
                disabled={isDuplicating}
              >
                <DocumentDuplicateIcon className="h-4 w-4 mr-1" />
                {trans('assessment_pages.common.duplicate')}
              </Button>
              <Button
                onClick={() => router.visit(route('teacher.assessments.edit', assessment.id))}
                color="secondary"
                variant='outline'
                size="sm"
              >
                {trans('assessment_pages.common.edit')}
              </Button>
            </div>
          }
        >
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <AssessmentHeader assessment={assessment} showDescription={true} showMetadata={false} />

              <Stat.Group columns={4} className="mt-6">
                <Stat.Item
                  title={trans('assessment_pages.common.questions')}
                  value={questionsCount}
                  icon={QuestionMarkCircleIcon}
                />
                <Stat.Item
                  title={trans('assessment_pages.common.total_points')}
                  value={totalPoints}
                  icon={StarIcon}
                />
                <Stat.Item
                  title={trans('assessment_pages.common.duration')}
                  value={formatDuration(assessment.duration_minutes)}
                  icon={ClockIcon}
                />
                <Stat.Item
                  title={trans('assessment_pages.common.concerned_students')}
                  value={totalStudents}
                  icon={UserGroupIcon}
                />
              </Stat.Group>
            </div>
          </div>
        </Section>

        <Section
          title={trans('assessment_pages.show.students_section_title')}
          subtitle={trans('assessment_pages.show.students_section_subtitle', { count: totalStudents })}
        >
          <DataTable data={assignments} config={assignmentsTableConfig} />
        </Section>

        <Section
          title={trans('assessment_pages.common.assessment_questions')}
          collapsible
        >
          {(assessment.questions ?? []).length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <p>{trans('assessment_pages.common.no_questions')}</p>
              <div className="mt-4">
                <Button onClick={() => router.visit(route('teacher.assessments.edit', assessment.id))}>
                  {trans('assessment_pages.common.add_questions')}
                </Button>
              </div>
            </div>
          ) : (
            <div className="divide-y divide-gray-200">
              {(assessment.questions ?? []).map((question, index) => (
                <QuestionReadOnlySection key={question.id} question={question} questionIndex={index}>
                  {question.type !== 'text' && (question.choices ?? []).length > 0 && (
                    <div className="ml-4">
                      <h5 className="text-sm font-medium text-gray-700 mb-2">
                        {trans('assessment_pages.common.answer_choices')}
                      </h5>
                      <div className="space-y-2">
                        <QuestionTeacherReadOnlyChoices
                          type={question.type}
                          choices={question.choices ?? []}
                        />
                      </div>
                    </div>
                  )}
                  {question.type === 'text' && (
                    <QuestionResultReadOnlyText
                      userText={trans('assessment_pages.common.free_text_info')}
                      label=""
                    />
                  )}
                </QuestionReadOnlySection>
              ))}
            </div>
          )}
        </Section>
      </div>

      <ConfirmationModal
        isOpen={showDuplicateModal}
        onClose={() => setShowDuplicateModal(false)}
        onConfirm={handleDuplicate}
        title={trans('assessment_pages.common.duplicate_modal_title')}
        message={trans('assessment_pages.common.duplicate_modal_message', { title: assessment.title })}
        confirmText={trans('assessment_pages.common.duplicate_confirm')}
        cancelText={trans('assessment_pages.create.cancel')}
        type="info"
        loading={isDuplicating}
      />
    </AuthenticatedLayout>
  );
};

export default AssessmentShow;

