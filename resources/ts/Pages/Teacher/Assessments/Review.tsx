import { useMemo, useCallback } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Assessment, AssessmentAssignment, Answer, User, Question, QuestionResult, Choice } from '@/types';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { Badge, Button, Section, QuestionRenderer, Stat } from '@/Components';
import { breadcrumbs, formatDate, trans } from '@/utils';
import { DocumentTextIcon, ChartPieIcon, CheckCircleIcon } from '@heroicons/react/24/outline';

interface Props {
  assessment: Assessment;
  student: User;
  assignment: AssessmentAssignment;
  userAnswers: Record<number, Answer>;
}

export default function ReviewAssignment({ assessment, student, assignment, userAnswers = {} }: Props) {
  const totalPoints = useMemo(() =>
    (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0),
    [assessment.questions]
  );

  const calculatedTotalScore = useMemo(() => {
    return Object.values(userAnswers || {}).reduce((sum, answer) => {
      return sum + (answer.score || 0);
    }, 0);
  }, [userAnswers]);

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
        score: 0
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
      score: answer.score || 0
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


  return (
    <AuthenticatedLayout
      title={trans('grading_pages.review.title', { student: student.name, assessment: assessment.title })}
      breadcrumb={breadcrumbs.assessmentReview(assessment, assignment, student)}
    >
      <div className="max-w-6xl mx-auto space-y-6">
        <Section
          title={trans('grading_pages.review.result_title', { student: student.name })}
          actions={
            <div className="flex items-center space-x-4">
              <Button
                onClick={() => router.visit(route('teacher.assessments.show', assessment.id))}
                variant="outline"
                size="sm"
              >
                {trans('grading_pages.show.back_to_assessment')}
              </Button>
              <Button
                onClick={() => router.visit(route('teacher.assessments.grade', { assessment: assessment.id, assignment: assignment.id }))}
                size="sm"
              >
                {trans('grading_pages.review.edit_grades')}
              </Button>
            </div>
          }
        >
          <Stat.Group columns={4}>
            <Stat.Item
              title={trans('grading_pages.show.total_score')}
              value={`${calculatedTotalScore} / ${totalPoints}`}
              icon={DocumentTextIcon}
            />
            <Stat.Item
              title={trans('grading_pages.show.percentage')}
              value={`${percentage}%`}
              icon={ChartPieIcon}
            />
            <Stat.Item
              title={trans('grading_pages.show.status')}
              value={
                <Badge
                  size='sm'
                  label={assignment.submitted_at ? trans('grading_pages.show.submitted') : trans('grading_pages.show.not_submitted')}
                  type={assignment.submitted_at ? 'success' : 'gray'}
                />
              }
              icon={CheckCircleIcon}
            />
            <Stat.Item
              title={trans('grading_pages.review.graded_at')}
              value={assignment.graded_at ? formatDate(assignment.graded_at, 'datetime') : '-'}
              icon={DocumentTextIcon}
            />
          </Stat.Group>
        </Section>

        <Section title={trans('grading_pages.review.questions_review')}>
          <div className="space-y-6">
            {(assessment.questions ?? []).map((question) => (
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

        {assignment.teacher_notes && (
          <Section title={trans('grading_pages.show.teacher_notes_label')}>
            <div className="p-4 bg-gray-50 rounded-lg">
              <p className="text-gray-700 whitespace-pre-wrap">{assignment.teacher_notes}</p>
            </div>
          </Section>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
