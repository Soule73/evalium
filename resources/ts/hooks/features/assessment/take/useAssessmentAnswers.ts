import { useEffect } from 'react';
import { Answer, Question } from '@/types';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

interface UseAssessmentAnswersParams {
  questions: Question[];
  userAnswers: Answer[];
}

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
    const initialAnswers: Record<number, string | number | number[]> = {};

    userAnswers.forEach((answer) => {
      if (answer.question_id) {
        const question = questions.find((q) => q.id === answer.question_id);

        if (question?.type === 'boolean' && answer.choices) {
          initialAnswers[answer.question_id] = answer.choices.map((c) => c.choice.id);
        } else if (question?.type === 'one_choice' && answer.choice_id) {
          initialAnswers[answer.question_id] = answer.choice_id;
        } else if (question?.type === 'text' && answer.answer_text) {
          initialAnswers[answer.question_id] = answer.answer_text;
        }
      }
    });

    setAnswers(initialAnswers);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const updateAnswer = (questionId: number, value: string | number | number[]) => {
    setAnswer(questionId, value);
  };

  return {
    updateAnswer,
  };
};
