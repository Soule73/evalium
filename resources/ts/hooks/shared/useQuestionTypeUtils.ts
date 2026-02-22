import { useCallback } from 'react';
import { useTranslations } from './useTranslations';

/**
 * Hook providing localized question type labels.
 */
export function useQuestionTypeUtils() {
    const { t } = useTranslations();

    const getTypeLabels = useCallback(
        (): Record<string, string> => ({
            multiple: t('components.take_question.multiple_choice'),
            one_choice: t('components.take_question.one_choice'),
            boolean: t('components.take_question.boolean'),
            text: t('components.take_question.text'),
            file: t('components.take_question.file'),
        }),
        [t],
    );

    const getTypeLabel = useCallback(
        (type: string): string => {
            return getTypeLabels()[type] ?? type;
        },
        [getTypeLabels],
    );

    return { getTypeLabels, getTypeLabel };
}
