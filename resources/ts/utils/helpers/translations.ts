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
    let translation: unknown = language;

    for (const k of keys) {
        if (translation && typeof translation === 'object' && k in translation) {
            translation = (translation as Record<string, unknown>)[k];
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

