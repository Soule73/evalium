import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type AssessmentAssignment, type Answer } from '@/types';
import { useAssessmentResults } from '@/hooks/features/assessment';
import useAssessmentScoring from '@/hooks/features/assessment/useAssessmentScoring';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { AlertEntry, Badge, Button, Section, QuestionRenderer, TextEntry } from '@/Components';
import { trans, formatDate } from '@/utils';
import { breadcrumbs } from '@/utils/helpers/breadcrumbs';

interface Props {
  assessment: Assessment;
  assignment: AssessmentAssignment;
  userAnswers: Record<number, Answer>;
}

const AssessmentResults: React.FC<Props> = ({ assessment, assignment, userAnswers }) => {
  const { isPendingReview, assignmentStatus, showCorrectAnswers, showResultsImmediately, assessmentIsActive, totalPoints, getQuestionResult } =
    useAssessmentResults({ assessment, assignment, userAnswers });

  const { finalScore, finalPercentage } = useAssessmentScoring({
    assessment,
    assignment,
    userAnswers,
    totalPoints,
    getQuestionResult,
  });

  const translations = {
    title: trans('student_assessment_pages.results.title', { assessment: assessment.title }),
    sectionTitle: trans('student_assessment_pages.results.section_title'),
    assessmentActive: trans('student_assessment_pages.results.assessment_active'),
    assessmentDisabled: trans('student_assessment_pages.results.assessment_disabled'),
    backToAssessments: trans('student_assessment_pages.results.back_to_assessments'),
    answersDetail: trans('student_assessment_pages.results.answers_detail'),
    teacherComments: trans('student_assessment_pages.results.teacher_comments'),
    yourScore: trans('student_assessment_pages.results.your_score'),
    percentage: trans('student_assessment_pages.results.percentage'),
    status: trans('student_assessment_pages.results.status'),
    submittedAt: trans('student_assessment_pages.results.submitted_at'),
    gradedAt: trans('student_assessment_pages.results.graded_at'),
    pendingReview: trans('student_assessment_pages.results.pending_review'),
    graded: trans('student_assessment_pages.results.graded'),
    subject: trans('student_assessment_pages.results.subject'),
    class: trans('student_assessment_pages.results.class'),
    teacher: trans('student_assessment_pages.results.teacher'),
    resultsHiddenTitle: trans('student_assessment_pages.results.results_hidden_title'),
    resultsHiddenMessage: trans('student_assessment_pages.results.results_hidden_message'),
  };

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.student.assessmentResults(assessment)}>
      <Section
        title={translations.sectionTitle}
        subtitle={
          <div className="flex items-center space-x-4">
            <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
              {assignmentStatus.label}
            </span>
            <div>
              {assessmentIsActive ? (
                <Badge label={translations.assessmentActive} type="success" />
              ) : (
                <Badge label={translations.assessmentDisabled} type="error" />
              )}
            </div>
          </div>
        }
        actions={
          <Button
            color="secondary"
            variant="outline"
            size="sm"
            className="w-max"
            onClick={() => router.visit(route('student.assessments.index'))}
          >
            {translations.backToAssessments}
          </Button>
        }
      >
        <div className="bg-white rounded-lg border border-gray-200 p-6 mb-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">{assessment.title}</h3>
              <div className="space-y-3">
                <TextEntry
                  label={translations.subject}
                  value={assessment.class_subject?.subject?.name || '-'}
                />
                <TextEntry label={translations.class} value={assessment.class_subject?.class?.name || '-'} />
                <TextEntry label={translations.teacher} value={assessment.class_subject?.teacher?.name || '-'} />
              </div>
            </div>

            <div className="border-l border-gray-200 pl-6">
              <div className="space-y-4">
                <div>
                  <p className="text-sm text-gray-600 mb-1">{translations.yourScore}</p>
                  <p className="text-3xl font-bold text-gray-900">
                    {Number(finalScore || 0).toFixed(2)} / {totalPoints}
                  </p>
                  <p className="text-lg text-gray-600">
                    {translations.percentage}: {Number(finalPercentage || 0).toFixed(1)}%
                  </p>
                </div>

                <div>
                  <p className="text-sm text-gray-600 mb-1">{translations.status}</p>
                  {isPendingReview ? (
                    <Badge label={translations.pendingReview} type="warning" />
                  ) : (
                    <Badge label={translations.graded} type="success" />
                  )}
                </div>

                {assignment.submitted_at && (
                  <TextEntry
                    label={translations.submittedAt}
                    value={formatDate(assignment.submitted_at)}
                  />
                )}
              </div>
            </div>
          </div>
        </div>
      </Section>

      <Section title={translations.answersDetail}>
        {assignment.teacher_notes && (
          <AlertEntry title={translations.teacherComments} type="info" className="mb-6">
            <p className="text-sm whitespace-pre-wrap">{assignment.teacher_notes}</p>
          </AlertEntry>
        )}

        {!showCorrectAnswers && !showResultsImmediately && isPendingReview && (
          <AlertEntry title={translations.resultsHiddenTitle} type="warning" className="mb-6">
            <p className="text-sm">{translations.resultsHiddenMessage}</p>
          </AlertEntry>
        )}

        <QuestionRenderer
          questions={assessment.questions || []}
          getQuestionResult={getQuestionResult}
          isTeacherView={false}
          showCorrectAnswers={showCorrectAnswers}
        />
      </Section>
    </AuthenticatedLayout>
  );
};

export default AssessmentResults;
