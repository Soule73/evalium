import { useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { type PageProps as InertiaPageProps } from '@inertiajs/core';
import { translateKey, type LanguageData } from '@/utils';

interface TranslationPageProps extends InertiaPageProps {
    locale: string;
    language: LanguageData;
}

/**
 * Hook providing pure translation functions safe for use inside useMemo/useCallback.
 *
 * Unlike the deprecated trans()/locale()/transAll()/transChoice() utilities
 * (which call usePage internally), the functions returned by this hook are NOT
 * React hooks and can be called anywhere: inside useMemo, useCallback, useEffect,
 * event handlers, loops, etc.
 *
 * @example
 * const { t, tAll, tChoice, currentLocale } = useTranslations();
 * const label = useMemo(() => t('common.search'), [t]);
 * const isFrench = currentLocale === 'fr';
 * const itemsLabel = tChoice('items.count', 5);
 */
export function useTranslations() {
    const { language, locale } = usePage<TranslationPageProps>().props;

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

    const tChoice = useCallback(
        (key: string, count: number, replacements: Record<string, string | number> = {}): string => {
            return translateKey(language, key, { count, ...replacements });
        },
        [language],
    );

    return { t, tAll, tChoice, currentLocale: locale } as const;
}
