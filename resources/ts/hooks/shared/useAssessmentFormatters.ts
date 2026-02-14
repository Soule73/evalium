import { useCallback } from 'react';
import { useTranslations } from './useTranslations';

/**
 * Hook providing localized assessment score formatting functions.
 */
export function useAssessmentFormatters() {
  const { t } = useTranslations();

  const formatAssessmentScore = useCallback((
    score: number | undefined,
    totalPoints: number,
    isPendingReview?: boolean,
    autoScore?: number
  ): string => {
    if (isPendingReview && autoScore !== undefined) {
      return t('formatters.partial_score_mcq', { score: autoScore, total: totalPoints });
    }
    return t('formatters.score_format', { score: score || 0, total: totalPoints });
  }, [t]);

  return { formatAssessmentScore };
}
