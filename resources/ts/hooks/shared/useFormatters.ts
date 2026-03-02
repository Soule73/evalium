import { useMemo } from 'react';
import { useTranslations } from './useTranslations';
import { createTranslatedFormatters } from '@evalium/utils/formatting/translatedFormatters';

/**
 * Hook providing localized formatting functions.
 */
export function useFormatters() {
    const { t } = useTranslations();
    return useMemo(() => createTranslatedFormatters(t), [t]);
}
