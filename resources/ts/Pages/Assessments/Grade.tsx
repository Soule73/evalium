import { useMemo, useState, useCallback } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type AssessmentAssignment, type Answer, type User, type Question, type PageProps, type QuestionResult, type Choice, type AssessmentRouteContext } from '@/types';
import { requiresManualGrading } from '@/utils';
import { route } from 'ziggy-js';
import { router, usePage } from '@inertiajs/react';
import { Button, Section, Textarea, QuestionRenderer, ConfirmationModal, Stat } from '@/Components';
import { hasPermission, breadcrumbs, trans } from '@/utils';
import { DocumentTextIcon, ChartPieIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';

interface Props {
  assessment: Assessment;
  student: User;
  assignment: AssessmentAssignment;
  userAnswers: Record<number, Answer>;
  routeContext?: AssessmentRouteContext;
}

export default function GradeAssignment({ assessment, student, assignment, userAnswers = {}, routeContext }: Props) {
  const { auth } = usePage<PageProps>().props;
  const canGradeAssessments = hasPermission(auth.permissions, 'grade assessments');

  const [scores, setScores] = useState<Record<number, number>>(() => {
    const initialScores: Record<number, number> = {};
    (assessment.questions ?? []).forEach(question => {
      const answer = userAnswers[question.id];
      initialScores[question.id] = answer?.score ?? 0;
    });
    return initialScores;
  });

  const [feedbacks, setFeedbacks] = useState<Record<number, string>>(() => {
    const initialFeedbacks: Record<number, string> = {};
    (assessment.questions ?? []).forEach(question => {
      const answer = userAnswers[question.id];
      if (answer?.feedback) {
        initialFeedbacks[question.id] = answer.feedback;
      }
    });
    return initialFeedbacks;
  });

  const [teacherNotes, setTeacherNotes] = useState(assignment.teacher_notes || '');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showConfirmModal, setShowConfirmModal] = useState(false);

  const totalPoints = useMemo(() =>
    (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0),
    [assessment.questions]
  );

  const calculatedTotalScore = useMemo(() =>
    Object.values(scores).reduce((sum, score) => sum + score, 0),
    [scores]
  );

  const percentage = useMemo(() =>
    totalPoints > 0 ? Math.round((calculatedTotalScore / totalPoints) * 100) : 0,
    [calculatedTotalScore, totalPoints]
  );

  const handleScoreChange = useCallback((questionId: number, value: string) => {
    const numValue = parseFloat(value) || 0;
    setScores(prev => ({ ...prev, [questionId]: numValue }));
  }, []);

  const handleFeedbackChange = useCallback((questionId: number, value: string) => {
    setFeedbacks(prev => ({ ...prev, [questionId]: value }));
  }, []);

  const getQuestionResult = useCallback((question: Question): QuestionResult => {
    const answer = userAnswers[question.id];

    if (!answer) {
      return {
        isCorrect: null,
        userChoices: [],
        hasMultipleAnswers: question.type === 'multiple',
        feedback: feedbacks[question.id] || null,
        score: scores[question.id] || 0
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
      feedback: feedbacks[question.id] || null,
      score: scores[question.id] || 0
    };
  }, [userAnswers, feedbacks, scores]);

  const handleSubmit = useCallback(() => {
    setShowConfirmModal(true);
  }, []);

  const saveGradeUrl = routeContext?.saveGradeRoute
    ? route(routeContext.saveGradeRoute, { assessment: assessment.id, assignment: assignment.id })
    : route('teacher.assessments.saveGrade', { assessment: assessment.id, assignment: assignment.id });

  const handleConfirmSubmit = useCallback(() => {
    setIsSubmitting(true);
    setShowConfirmModal(false);

    const scoresWithFeedback = (assessment.questions ?? []).map(question => ({
      question_id: question.id,
      score: scores[question.id] || 0,
      feedback: feedbacks[question.id] || null
    }));

    router.post(saveGradeUrl, {
      scores: scoresWithFeedback,
      teacher_notes: teacherNotes
    }, {
      onFinish: () => setIsSubmitting(false)
    });
  }, [assessment, scores, feedbacks, teacherNotes, saveGradeUrl]);

  const renderScoreInput = (question: Question) => {
    const questionScore = scores[question.id] || 0;
    const maxScore = question.points || 0;
    const isAutoGraded = !requiresManualGrading(question);

    return (
      <div className="mt-4 p-4 bg-gray-50 rounded-lg space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <label className="text-sm font-medium text-gray-700">
              {trans('grading_pages.show.question_score_label', { max: maxScore })}
            </label>
            {isAutoGraded && (
              <p className="text-xs text-blue-600 mt-1">
                {trans('grading_pages.show.auto_graded_info')}
              </p>
            )}
            {!isAutoGraded && (
              <p className="text-xs text-orange-600 mt-1">
                {trans('grading_pages.show.manual_grading_required')}
              </p>
            )}
          </div>
          <div className="flex items-center space-x-2">
            <input
              type="number"
              min="0"
              max={maxScore}
              step="0.5"
              value={questionScore}
              onChange={(e) => handleScoreChange(question.id, e.target.value)}
              className="w-20 px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              disabled={!canGradeAssessments}
            />
            <span className="text-sm text-gray-500">/ {maxScore}</span>
          </div>
        </div>

        <div>
          <Textarea
            label={trans('grading_pages.show.question_comment_label')}
            value={feedbacks[question.id] || ''}
            onChange={(e) => handleFeedbackChange(question.id, e.target.value)}
            placeholder={trans('grading_pages.show.question_comment_placeholder')}
            rows={2}
            disabled={!canGradeAssessments}
          />
        </div>
      </div>
    );
  };

  const pageBreadcrumbs = routeContext
    ? breadcrumbs.assessment.grade(routeContext, assessment, student)
    : breadcrumbs.assessmentGrade(assessment, assignment, student);

  const backRoute = routeContext
    ? route(routeContext.showRoute, assessment.id)
    : route('teacher.assessments.show', assessment.id);

  return (
    <AuthenticatedLayout
      title={trans('grading_pages.show.title', { student: student.name, assessment: assessment.title })}
      breadcrumb={pageBreadcrumbs}
    >
      <div className="max-w-6xl mx-auto space-y-6">
        <Section
          title={trans('grading_pages.show.correction_title', { student: student.name })}
          actions={
            <div className="flex items-center space-x-4">
              <Button
                onClick={() => router.visit(backRoute)}
                variant="outline"
                size="sm"
              >
                {trans('grading_pages.show.back_to_assessment')}
              </Button>
              {canGradeAssessments && (
                <Button
                  onClick={handleSubmit}
                  disabled={isSubmitting}
                  loading={isSubmitting}
                  size="sm"
                >
                  {isSubmitting ? trans('grading_pages.show.saving') : trans('grading_pages.show.save_grades')}
                </Button>
              )}
            </div>
          }
        >
          <Stat.Group columns={3}>
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
              value={assignment.submitted_at ? trans('grading_pages.show.submitted') : trans('grading_pages.show.not_submitted')}
              icon={assignment.submitted_at ? CheckCircleIcon : XCircleIcon}
            />
          </Stat.Group>
        </Section>

        <Section title={trans('grading_pages.show.questions_correction')}>
          <div className="space-y-6">
            {(assessment.questions ?? []).map((question) => (
              <div key={question.id} className="pb-6 border-b border-gray-200 last:border-0">
                <QuestionRenderer
                  questions={[question]}
                  getQuestionResult={getQuestionResult}
                  scores={scores}
                  isTeacherView={true}
                  renderScoreInput={renderScoreInput}
                  isEditMode={canGradeAssessments}
                />
              </div>
            ))}
          </div>
        </Section>

        <Section title={trans('grading_pages.show.teacher_notes_label')}>
          <Textarea
            value={teacherNotes}
            onChange={(e) => setTeacherNotes(e.target.value)}
            placeholder={trans('grading_pages.show.teacher_notes_placeholder')}
            rows={4}
            disabled={!canGradeAssessments}
            helperText={trans('grading_pages.show.teacher_notes_help')}
          />
        </Section>
      </div>

      <ConfirmationModal
        isOpen={showConfirmModal}
        onClose={() => setShowConfirmModal(false)}
        onConfirm={handleConfirmSubmit}
        title={trans('grading_pages.show.confirm_save_title')}
        message={trans('grading_pages.show.confirm_save_message', { student: student.name })}
        confirmText={trans('grading_pages.show.confirm_save')}
        cancelText={trans('grading_pages.show.cancel')}
        type="info"
        loading={isSubmitting}
      />
    </AuthenticatedLayout>
  );
}
