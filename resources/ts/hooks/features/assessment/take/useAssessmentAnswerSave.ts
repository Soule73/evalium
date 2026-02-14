import { useCallback, useRef } from 'react';
import { route } from 'ziggy-js';
import axios from 'axios';

interface UseAssessmentAnswerSaveParams {
  assessmentId: number;
}

/**
 * Custom hook to handle saving assessment answers to the server.
 * Provides methods for saving individual answers or all answers at once.
 */
export const useAssessmentAnswerSave = ({ assessmentId }: UseAssessmentAnswerSaveParams) => {
  const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const saveAnswerIndividual = useCallback(
    (_questionId: number, _value: string | number | number[], allAnswers: Record<number, string | number | number[]>) => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
      }

      saveTimeoutRef.current = setTimeout(() => {
        axios.post(route('student.assessments.save-answers', assessmentId), { answers: allAnswers })
          .catch(() => { });
      }, 500);
    },
    [assessmentId]
  );

  const saveAllAnswers = useCallback(
    async (answers: Record<number, string | number | number[]>) => {
      try {
        await axios.post(route('student.assessments.save-answers', assessmentId), { answers });
      } catch (error) {
        const message = error instanceof Error ? error.message : 'Unknown error';
        throw new Error(`Failed to save answers: ${message}`, { cause: error });
      }
    },
    [assessmentId]
  );

  const forceSave = useCallback(
    async (answers: Record<number, string | number | number[]>) => {
      if (saveTimeoutRef.current) {
        clearTimeout(saveTimeoutRef.current);
        saveTimeoutRef.current = null;
      }
      return saveAllAnswers(answers);
    },
    [saveAllAnswers]
  );

  const cleanup = useCallback(() => {
    if (saveTimeoutRef.current) {
      clearTimeout(saveTimeoutRef.current);
      saveTimeoutRef.current = null;
    }
  }, []);

  return {
    saveAnswerIndividual,
    saveAllAnswers,
    forceSave,
    cleanup,
  };
};
