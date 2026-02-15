import { useMemo, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Badge, Button, Section, Stat, QuestionRenderer } from '@/Components';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatDate } from '@/utils';
import type { Enrollment, Assessment, AssessmentAssignment, Answer, Question, QuestionResult, Choice, PageProps } from '@/types';
import { DocumentTextIcon, ChartPieIcon, CheckCircleIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  enrollment: Enrollment;
  assignment: AssessmentAssignment;
  assessment: Assessment;
  userAnswers: Record<number, Answer>;
}

export default function EnrollmentAssignmentShow({ enrollment, assignment, assessment, userAnswers = {} }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const totalPoints = useMemo(() =>
    (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0),
    [assessment.questions]
  );

  const calculatedTotalScore = useMemo(() =>
    Object.values(userAnswers || {}).reduce((sum, answer) => sum + (answer.score || 0), 0),
    [userAnswers]
  );

  const percentage = useMemo(() =>
    totalPoints > 0 ? Math.round((calculatedTotalScore / totalPoints) * 100) : 0,
    [calculatedTotalScore, totalPoints]
  );

  const getQuestionResult = useCallback((question: Question): QuestionResult => {
    const answer = userAnswers[question.id];

    if (!answer) {
      return {
        isCorrect: null,
        userChoices: [],
        hasMultipleAnswers: question.type === 'multiple',
        feedback: null,
        score: 0,
      };
    }

    const isMultipleChoice = question.type === 'multiple';
    const userChoices: Choice[] = [];

    if (isMultipleChoice && answer.choices) {
      answer.choices.forEach(c => {
        if (c.choice) {
          userChoices.push(c.choice);
        }
      });
    } else if (answer.choice) {
      userChoices.push(answer.choice);
    }

    return {
      isCorrect: null,
      userChoices,
      hasMultipleAnswers: isMultipleChoice,
      userText: answer.answer_text,
      feedback: answer.feedback || null,
      score: answer.score || 0,
    };
  }, [userAnswers]);

  const scores = useMemo(() => {
    const result: Record<number, number> = {};
    Object.values(userAnswers || {}).forEach(answer => {
      if (answer.question_id) {
        result[answer.question_id] = answer.score || 0;
      }
    });
    return result;
  }, [userAnswers]);

  const pageBreadcrumbs = [
    ...breadcrumbs.admin.showEnrollment(enrollment),
    {
      label: t('breadcrumbs.enrollment_assignments'),
      href: route('admin.enrollments.assignments', enrollment.id),
    },
    { label: assessment.title },
  ];

  return (
    <AuthenticatedLayout
      title={t('admin_pages.enrollments.assignment_show_title')}
      breadcrumb={pageBreadcrumbs}
    >
      <div className="max-w-6xl mx-auto space-y-6">
        <Section
          title={assessment.title}
          subtitle={t('admin_pages.enrollments.assignment_show_subtitle')}
          actions={
            <Button
              size="sm"
              variant="outline"
              color="secondary"
              onClick={() => router.visit(route('admin.enrollments.assignments', enrollment.id))}
            >
              {t('admin_pages.enrollments.back_to_assignments')}
            </Button>
          }
        >
          <Stat.Group columns={4}>
            <Stat.Item
              title={t('admin_pages.enrollments.score')}
              value={assignment.score !== null && assignment.score !== undefined
                ? `${calculatedTotalScore} / ${totalPoints}`
                : '-'}
              icon={DocumentTextIcon}
            />
            <Stat.Item
              title={t('grading_pages.show.percentage')}
              value={assignment.score !== null ? `${percentage}%` : '-'}
              icon={ChartPieIcon}
            />
            <Stat.Item
              title={t('admin_pages.enrollments.assignment_status')}
              value={
                <Badge
                  size="sm"
                  label={assignment.submitted_at
                    ? (assignment.graded_at
                      ? t('components.assignment_list.status_graded')
                      : t('components.assignment_list.status_pending_grading'))
                    : t('components.assignment_list.status_not_started')}
                  type={assignment.graded_at ? 'success' : (assignment.submitted_at ? 'warning' : 'gray')}
                />
              }
              icon={CheckCircleIcon}
            />
            <Stat.Item
              title={t('admin_pages.enrollments.submitted_at')}
              value={assignment.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
              icon={CalendarIcon}
            />
          </Stat.Group>
        </Section>

        {assessment.questions && assessment.questions.length > 0 && (
          <Section title={t('grading_pages.review.questions_review')}>
            <div className="space-y-6">
              {assessment.questions.map((question) => (
                <div key={question.id} className="pb-6 border-b border-gray-200 last:border-0">
                  <QuestionRenderer
                    questions={[question]}
                    getQuestionResult={getQuestionResult}
                    scores={scores}
                    isTeacherView={true}
                  />
                </div>
              ))}
            </div>
          </Section>
        )}

        {assignment.teacher_notes && (
          <Section title={t('grading_pages.show.teacher_notes_label')}>
            <div className="p-4 bg-gray-50 rounded-lg">
              <p className="text-gray-700 whitespace-pre-wrap">{assignment.teacher_notes}</p>
            </div>
          </Section>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
