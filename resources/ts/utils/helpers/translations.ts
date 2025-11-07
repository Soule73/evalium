import { usePage } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';

/**
 * Interface for page props shared via Inertia
 */
interface PageProps extends InertiaPageProps {
    locale: string;
    language: Record<string, Record<string, string | Record<string, string>>>;
}

/**
 * Get a translation by key using dot notation
 * 
 * @example
 * // From lang/fr/exams.php -> 'created' key
 * trans('exams.created') // "Examen créé avec succès !"
 * 
 * // With replacements
 * trans('groups.students_assigned', { count: 5 }) // "5 étudiant(s) assigné(s) avec succès !"
 * 
 * // From nested keys
 * trans('actions.named.create', { name: 'Examen' }) // "Créer Examen"
 * 
 * @param key - Translation key in dot notation (e.g., 'exams.created')
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

    // Split the key into parts (e.g., 'exams.created' -> ['exams', 'created'])
    const keys = key.split('.');
    let translation: any = language;

    // Navigate through the nested object
    for (const k of keys) {
        if (translation && typeof translation === 'object' && k in translation) {
            translation = translation[k];
        } else {
            // Key not found, return fallback or key itself
            return fallback || key;
        }
    }

    // If translation is not a string, return fallback or key
    if (typeof translation !== 'string') {
        return fallback || key;
    }

    // Replace placeholders with values
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
 * // Get all exam translations
 * const examTranslations = transAll('exams');
 * // { created: "Examen créé avec succès !", updated: "...", ... }
 * 
 * @param namespace - Translation namespace (e.g., 'exams', 'groups')
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
 * transChoice('groups.students_assigned', 1) // "1 étudiant assigné"
 * transChoice('groups.students_assigned', 5) // "5 étudiants assignés"
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
