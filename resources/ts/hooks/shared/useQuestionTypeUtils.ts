import { useCallback } from 'react';
import { useTranslations } from './useTranslations';

/**
 * Hook providing localized question type labels.
 * Replaces direct trans() calls in questionTypeUtils for React hook safety.
 */
export function useQuestionTypeUtils() {
  const { t } = useTranslations();

  const getTypeLabels = useCallback((): Record<string, string> => ({
    multiple: t('components.take_question.multiple_choice'),
    one_choice: t('components.take_question.one_choice'),
    boolean: t('components.take_question.boolean'),
    text: t('components.take_question.text'),
  }), [t]);

  const getTypeLabel = useCallback((type: string): string => {
    return getTypeLabels()[type] ?? type;
  }, [getTypeLabels]);

  return { getTypeLabels, getTypeLabel };
}
