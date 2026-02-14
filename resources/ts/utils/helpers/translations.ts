import { usePage } from '@inertiajs/react';
import { type PageProps as InertiaPageProps } from '@inertiajs/core';

interface PageProps extends InertiaPageProps {
    locale: string;
    language: Record<string, Record<string, string | Record<string, string>>>;
}

export type LanguageData = Record<string, Record<string, string | Record<string, string>>>;

/**
 * Pure translation function that resolves a key using dot notation.
 *
 * This is NOT a React hook and can safely be called inside useMemo,
 * useCallback, event handlers, or any non-hook context.
 *
 * @param language - The language data object from page props
 * @param key - Translation key in dot notation (e.g., 'assessment.created')
 * @param replacements - Object with replacement values for :placeholder syntax
 * @param fallback - Fallback text if translation not found
 * @returns Translated string
 */
export function translateKey(
    language: LanguageData,
    key: string,
    replacements: Record<string, string | number> = {},
    fallback?: string
): string {
    const keys = key.split('.');
    let translation: any = language;

    for (const k of keys) {
        if (translation && typeof translation === 'object' && k in translation) {
            translation = translation[k];
        } else {
            return fallback || key;
        }
    }

    if (typeof translation !== 'string') {
        return fallback || key;
    }

    return Object.entries(replacements).reduce(
        (str, [placeholder, value]) => str.replace(`:${placeholder}`, String(value)),
        translation
    );
}

/**
 * Get a translation by key using dot notation (React hook - calls usePage).
 *
 * WARNING: This function calls usePage() internally, making it a React hook.
 * Do NOT call inside useMemo, useCallback, useEffect, or loops/conditions.
 * For those contexts, use the useTranslations() hook instead.
 *
 * @param key - Translation key in dot notation (e.g., 'assessment.created')
 * @param replacements - Object with replacement values for :placeholder syntax
 * @param fallback - Fallback text if translation not found
 * @returns Translated string
 */
export function trans(
    key: string,
    replacements: Record<string, string | number> = {},
    fallback?: string
): string {
    // eslint-disable-next-line react-hooks/rules-of-hooks
    const { language } = usePage<PageProps>().props;
    return translateKey(language, key, replacements, fallback);
}

/**
 * Get the current locale (React hook - calls usePage)
 */
export function locale(): string {
    // eslint-disable-next-line react-hooks/rules-of-hooks
    const { locale } = usePage<PageProps>().props;
    return locale;
}

/**
 * Check if current locale matches given locale (React hook - calls usePage)
 */
export function isLocale(loc: string): boolean {
    return locale() === loc;
}

/**
 * Get all translations for a namespace (React hook - calls usePage)
 *
 * @param namespace - Translation namespace (e.g., 'assessment', 'groups')
 * @returns Object with all translations in that namespace
 */
export function transAll(namespace: string): Record<string, any> {
    // eslint-disable-next-line react-hooks/rules-of-hooks
    const { language } = usePage<PageProps>().props;
    return language[namespace] || {};
}

/**
 * Choice translation with pluralization (React hook - calls usePage)
 *
 * @param key - Translation key
 * @param count - Count for pluralization
 * @param replacements - Additional replacements
 * @returns Translated string with count
 */
export function transChoice(
    key: string,
    count: number,
    replacements: Record<string, string | number> = {}
): string {
    const translation = trans(key, { count, ...replacements });
    return translation;
}
