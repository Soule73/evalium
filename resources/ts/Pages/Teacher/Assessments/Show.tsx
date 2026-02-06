import React, { useState, useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { formatDuration } from '@/utils';
import { Button, ConfirmationModal, Section, StatCard } from '@/Components';
import { Toggle } from '@examena/ui';
import { Assessment } from '@/types';
import { ClockIcon, QuestionMarkCircleIcon, StarIcon, DocumentDuplicateIcon, UserGroupIcon, AcademicCapIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { QuestionReadOnlySection } from '@/Components';
import { QuestionResultReadOnlyText, QuestionTeacherReadOnlyChoices } from '@/Components/features/assessment/QuestionResultReadOnly';
import { AssessmentHeader } from '@/Components/features/assessment/AssessmentHeader';

interface Props {
  assessment: Assessment;
}

const AssessmentShow: React.FC<Props> = ({ assessment }) => {
  const [isToggling, setIsToggling] = useState(false);
  const [isDuplicating, setIsDuplicating] = useState(false);
  const [showDuplicateModal, setShowDuplicateModal] = useState(false);

  const totalPoints = useMemo(() =>
    (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0),
    [assessment.questions]
  );

  const totalStudents = assessment.class_subject?.class?.active_enrollments_count || 0;

  const questionsCount = (assessment.questions ?? []).length;

  const handleToggleStatus = () => {
    if (isToggling) return;

    setIsToggling(true);
    const routeName = assessment.is_published ? 'teacher.assessments.unpublish' : 'teacher.assessments.publish';

    router.post(
      route(routeName, assessment.id),
      {},
      {
        preserveScroll: true,
        onFinish: () => setIsToggling(false),
      }
    );
  };

  const handleDuplicate = () => {
    if (isDuplicating) return;

    setIsDuplicating(true);
    router.post(
      route('teacher.assessments.duplicate', assessment.id),
      {},
      {
        onFinish: () => {
          setIsDuplicating(false);
          setShowDuplicateModal(false);
        },
      }
    );
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
              <Button
                onClick={() => router.visit(route('teacher.grading.index', assessment.id))}
                color="secondary"
                variant='outline'
                size="sm"
              >
                {trans('assessment_pages.common.view_assignments')}
              </Button>
            </div>
          }
        >
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <AssessmentHeader assessment={assessment} showDescription={true} showMetadata={false} />

              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                <StatCard
                  title={trans('assessment_pages.common.questions')}
                  value={questionsCount}
                  icon={QuestionMarkCircleIcon}
                  color='blue'
                />
                <StatCard
                  title={trans('assessment_pages.common.total_points')}
                  value={totalPoints}
                  color='green'
                  icon={StarIcon}
                />
                <StatCard
                  title={trans('assessment_pages.common.duration')}
                  value={formatDuration(assessment.duration_minutes)}
                  color='yellow'
                  icon={ClockIcon}
                />
                <StatCard
                  title={trans('assessment_pages.common.assigned_classes')}
                  value={1}
                  color='purple'
                  icon={UserGroupIcon}
                />
              </div>

              {totalStudents > 0 && (
                <div className="mt-4">
                  <StatCard
                    title={trans('assessment_pages.common.concerned_students')}
                    value={totalStudents}
                    color='blue'
                    icon={AcademicCapIcon}
                  />
                </div>
              )}
            </div>
          </div>
        </Section>

        <Section
          title={trans('assessment_pages.common.assessment_questions')}
          collapsible
        >
          {(assessment.questions ?? []).length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <p>{trans('assessment_pages.common.no_questions')}</p>
              <div className="mt-4">
                <Button>{trans('assessment_pages.common.add_questions')}</Button>
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

