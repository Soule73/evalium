import { useEffect } from 'react';
import { type Answer, type Question } from '@/types';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

interface UseAssessmentAnswersParams {
  questions: Question[];
  userAnswers: Answer[];
}

/**
 * Build initial answers map from raw server answer records and question metadata.
 *
 * @param questions  The assessment questions with type info
 * @param userAnswers  Raw answer records (one row per choice for multiple)
 * @returns Answers keyed by question id
 */
export const buildInitialAnswers = (
  questions: Question[],
  userAnswers: Answer[],
): Record<number, string | number | number[]> => {
  const initialAnswers: Record<number, string | number | number[]> = {};

  userAnswers.forEach((answer) => {
    if (answer.question_id) {
      const question = questions.find((q) => q.id === answer.question_id);

      if (question?.type === 'multiple' && answer.choice_id) {
        const existing = Array.isArray(initialAnswers[answer.question_id])
          ? (initialAnswers[answer.question_id] as number[])
          : [];
        initialAnswers[answer.question_id] = [...existing, answer.choice_id];
      } else if ((question?.type === 'boolean' || question?.type === 'one_choice') && answer.choice_id) {
        initialAnswers[answer.question_id] = answer.choice_id;
      } else if (question?.type === 'text' && answer.answer_text) {
        initialAnswers[answer.question_id] = answer.answer_text;
      }
    }
  });

  return initialAnswers;
};

/**
 * Custom hook to manage assessment answers state.
 * Initializes answers from existing userAnswers and provides update functionality.
 */
export const useAssessmentAnswers = ({ questions, userAnswers }: UseAssessmentAnswersParams) => {
  const { setAnswers, setAnswer } = useAssessmentTakeStore(useShallow((state) => ({
    setAnswers: state.setAnswers,
    setAnswer: state.setAnswer,
  })));

  useEffect(() => {
    setAnswers(buildInitialAnswers(questions, userAnswers));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const updateAnswer = (questionId: number, value: string | number | number[]) => {
    setAnswer(questionId, value);
  };

  return {
    updateAnswer,
  };
};
