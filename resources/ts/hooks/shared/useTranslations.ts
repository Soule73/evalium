import { useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { translateKey, type LanguageData } from '@/utils';

interface TranslationPageProps extends InertiaPageProps {
    language: LanguageData;
}

/**
 * Hook providing pure translation functions safe for use inside useMemo/useCallback.
 *
 * Unlike the trans() utility (which calls usePage internally), the t() function
 * returned by this hook is NOT a React hook and can be called anywhere:
 * inside useMemo, useCallback, useEffect, event handlers, loops, etc.
 *
 * @example
 * const { t } = useTranslations();
 * const label = useMemo(() => t('common.search'), [t]);
 */
export function useTranslations() {
    const { language } = usePage<TranslationPageProps>().props;

    const t = useCallback(
        (key: string, replacements: Record<string, string | number> = {}, fallback?: string): string => {
            return translateKey(language, key, replacements, fallback);
        },
        [language],
    );

    const tAll = useCallback(
        (namespace: string): Record<string, any> => {
            return language[namespace] || {};
        },
        [language],
    );

    return { t, tAll } as const;
}
