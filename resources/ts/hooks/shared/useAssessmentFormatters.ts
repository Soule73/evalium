import { useCallback } from 'react';
import { useTranslations } from './useTranslations';
import { formatAssessmentScore as formatScore } from '@evalium/utils/formatting/translatedFormatters';

/**
 * Hook providing localized assessment score formatting functions.
 */
export function useAssessmentFormatters() {
    const { t } = useTranslations();

    const formatAssessmentScore = useCallback(
        (
            score: number | undefined,
            totalPoints: number,
            isPendingReview?: boolean,
            autoScore?: number,
        ): string => {
            return formatScore(t, score, totalPoints, isPendingReview, autoScore);
        },
        [t],
    );

    return { formatAssessmentScore };
}
