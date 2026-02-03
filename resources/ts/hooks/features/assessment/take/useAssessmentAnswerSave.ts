import { useCallback, useRef } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';

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
        router.post(
          route('student.mcd.assessments.save-answers', assessmentId),
          { answers: allAnswers },
          {
            preserveScroll: true,
            preserveState: true,
            only: [],
          }
        );
      }, 500);
    },
    [assessmentId]
  );

  const saveAllAnswers = useCallback(
    async (answers: Record<number, string | number | number[]>) => {
      return new Promise<void>((resolve, reject) => {
        router.post(
          route('student.mcd.assessments.save-answers', assessmentId),
          { answers },
          {
            preserveScroll: true,
            preserveState: true,
            only: [],
            onSuccess: () => resolve(),
            onError: () => reject(new Error('Failed to save answers')),
          }
        );
      });
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
