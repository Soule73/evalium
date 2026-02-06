import { usePage } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';

interface PageProps extends InertiaPageProps {
    locale: string;
    language: Record<string, Record<string, string | Record<string, string>>>;
}

/**
 * Get a translation by key using dot notation
 * 
 * @example
 * // From lang/fr/assessment.php -> 'created' key
 * trans('assessment.created') // "Assessment created successfully!"
 * 
 * // With replacements
 * trans('groups.students_assigned', { count: 5 }) // "5 students assigned"
 * 
 * // From nested keys
 * trans('actions.named.create', { name: 'Assessment' }) // "Assessment created successfully!"
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
    const { language } = usePage<PageProps>().props;

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
 * Get the current locale
 * 
 * @returns Current locale (e.g., 'fr', 'en')
 */
export function locale(): string {
    const { locale } = usePage<PageProps>().props;
    return locale;
}

/**
 * Check if current locale matches given locale
 * 
 * @param loc - Locale to check (e.g., 'fr', 'en')
 * @returns True if current locale matches
 */
export function isLocale(loc: string): boolean {
    return locale() === loc;
}

/**
 * Get all translations for a namespace
 * 
 * @example
 * // Get all assessment translations
 * const assessmentTranslations = transAll('assessment');
 * // { created: "Assessment created successfully!", updated: "...", ... }
 * 
 * @param namespace - Translation namespace (e.g., 'assessment', 'groups')
 * @returns Object with all translations in that namespace
 */
export function transAll(namespace: string): Record<string, any> {
    const { language } = usePage<PageProps>().props;
    return language[namespace] || {};
}

/**
 * Choice translation (pluralization)
 * Simple implementation for count-based translations
 * 
 * @example
 * transChoice('groups.students_assigned', 1) // "1 student assigned"
 * transChoice('groups.students_assigned', 5) // "5 students assigned"
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
