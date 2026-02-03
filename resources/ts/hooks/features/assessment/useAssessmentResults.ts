import { Assessment, AssessmentAssignment, Question } from '@/types';
import { canShowExamResults, formatExamAssignmentStatus } from '@/utils';
import { useMemo } from 'react';

interface UseAssessmentResultParams {
  assessment: Assessment;
  assignment: AssessmentAssignment;
  userAnswers: Record<number, any>;
}

/**
 * Custom React hook to compute and provide assessment result-related data and utilities.
 */
const useAssessmentResults = ({ assessment, assignment, userAnswers }: UseAssessmentResultParams) => {
  const questions = assessment?.questions ?? [];
  const assessmentIsActive = assessment.is_published;
  const assignmentScore = assignment.score;
  const assignmentAutoScore = assignment.auto_score;
  const assignmentStatus = assignment.status;

  const totalPoints = useMemo(() => questions.reduce((sum, q) => sum + (q.points || 0), 0), [questions]);

  const finalScore = useMemo(() => assignmentScore ?? assignmentAutoScore, [assignmentScore, assignmentAutoScore]);

  const isPendingReview = useMemo(() => assignmentStatus !== 'graded', [assignmentStatus]);

  const formattedAssignmentStatus = useMemo(() => formatExamAssignmentStatus(assignmentStatus), [assignmentStatus]);

  const showCorrectAnswers = useMemo(() => canShowExamResults(assignmentStatus), [assignmentStatus]);

  const getQuestionResult = useMemo(() => {
    return (question: Question) => {
      const userAnswer = userAnswers[question.id];

      if (!userAnswer) {
        return {
          isCorrect: null,
          userChoices: [],
          hasMultipleAnswers: false,
          feedback: null,
        };
      }

      if (question.type === 'multiple') {
        if (userAnswer.type === 'multiple' && userAnswer.choices) {
          const selectedChoices = userAnswer.choices.map((c: { choice: { id: number } }) => c.choice);
          const correctChoices = (question.choices ?? []).filter((c) => c.is_correct);

          const selectedChoiceIds = new Set(selectedChoices.map((choice: { id: number }) => choice.id));
          const correctChoiceIds = new Set(correctChoices.map((choice) => choice.id));

          const hasAllCorrectChoices =
            correctChoiceIds.size === selectedChoiceIds.size &&
            [...correctChoiceIds].every((id) => selectedChoiceIds.has(id));

          const score =
            userAnswer.score !== undefined && userAnswer.score !== null
              ? userAnswer.score
              : hasAllCorrectChoices
                ? question.points
                : 0;

          return {
            isCorrect: hasAllCorrectChoices,
            userChoices: selectedChoices,
            hasMultipleAnswers: true,
            feedback: userAnswer.feedback,
            score: score,
          };
        }

        return {
          isCorrect: null,
          userChoices: [],
          hasMultipleAnswers: true,
          feedback: userAnswer.feedback,
          score: userAnswer.score ?? 0,
        };
      }

      if (question.type === 'text') {
        return {
          isCorrect: null,
          userChoices: [],
          userText: userAnswer.answer_text,
          hasMultipleAnswers: false,
          score: userAnswer.score,
          feedback: userAnswer.feedback,
        };
      }

      if (question.type === 'one_choice') {
        if (userAnswer.type === 'single' && userAnswer.choice) {
          const selectedChoice = userAnswer.choice;
          const isCorrect = selectedChoice.is_correct;

          const score =
            userAnswer.score !== undefined && userAnswer.score !== null
              ? userAnswer.score
              : isCorrect
                ? question.points
                : 0;

          return {
            isCorrect,
            userChoices: [selectedChoice],
            hasMultipleAnswers: false,
            feedback: userAnswer.feedback,
            score: score,
          };
        }

        return {
          isCorrect: null,
          userChoices: [],
          hasMultipleAnswers: false,
          feedback: userAnswer.feedback,
          score: userAnswer.score ?? 0,
        };
      }

      return {
        isCorrect: null,
        userChoices: [],
        hasMultipleAnswers: false,
        feedback: null,
      };
    };
  }, [userAnswers]);

  return {
    totalPoints,
    finalScore,
    isPendingReview,
    assignmentStatus: formattedAssignmentStatus,
    showCorrectAnswers,
    getQuestionResult,
    assessmentIsActive,
  };
};

export default useAssessmentResults;
