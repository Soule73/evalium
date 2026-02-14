import { useCallback } from 'react';
import { useTranslations } from './useTranslations';

/**
 * Hook providing localized choice display labels for assessment questions.
 * Replaces direct trans() calls in choiceUtils for React hook safety.
 */
export function useChoiceUtils() {
  const { t } = useTranslations();

  const getBooleanLabel = useCallback((isTrue: boolean): string => {
    return isTrue
      ? t('components.take_question.true')
      : t('components.take_question.false');
  }, [t]);

  const getBooleanShortLabel = useCallback((isTrue: boolean): string => {
    return isTrue
      ? t('components.question_result_readonly.boolean_true_short')
      : t('components.question_result_readonly.boolean_false_short');
  }, [t]);

  const getStatusLabelText = useCallback((
    isSelected: boolean,
    isCorrect: boolean,
    shouldShowCorrect: boolean,
    isTeacherView: boolean
  ): string | null => {
    if (!shouldShowCorrect) {
      return isSelected
        ? isTeacherView
          ? t('components.question_result_readonly.student_answer')
          : t('components.question_result_readonly.your_answer')
        : null;
    }

    if (isSelected && !isCorrect) {
      return isTeacherView
        ? t('components.question_result_readonly.student_answer_incorrect')
        : t('components.question_result_readonly.your_answer_incorrect');
    }

    if (isSelected && isCorrect) {
      return isTeacherView
        ? t('components.question_result_readonly.student_answer_correct')
        : t('components.question_result_readonly.your_answer_correct');
    }

    if (!isSelected && isCorrect) {
      return t('components.question_result_readonly.correct_answer');
    }

    return null;
  }, [t]);

  return { getBooleanLabel, getBooleanShortLabel, getStatusLabelText };
}
