import { useCallback } from 'react';
import { useTranslations } from './useTranslations';
import {
    getBooleanLabel as getBooleanLabelFn,
    getBooleanShortLabel as getBooleanShortLabelFn,
    getStatusLabelText as getStatusLabelTextFn,
} from '@evalium/utils/formatting/translatedFormatters';

/**
 * Hook providing localized choice display labels for assessment questions.
 */
export function useChoiceUtils() {
    const { t } = useTranslations();

    const getBooleanLabel = useCallback(
        (isTrue: boolean): string => getBooleanLabelFn(t, isTrue),
        [t],
    );

    const getBooleanShortLabel = useCallback(
        (isTrue: boolean): string => getBooleanShortLabelFn(t, isTrue),
        [t],
    );

    const getStatusLabelText = useCallback(
        (
            isSelected: boolean,
            isCorrect: boolean,
            shouldShowCorrect: boolean,
            isTeacherView: boolean,
        ): string | null =>
            getStatusLabelTextFn(t, isSelected, isCorrect, shouldShowCorrect, isTeacherView),
        [t],
    );

    return { getBooleanLabel, getBooleanShortLabel, getStatusLabelText };
}
