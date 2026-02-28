/**
 * Formats a given number of seconds into a time string.
 *
 * - If the input is negative, returns '00:00'.
 * - If the time is one hour or more, returns a string in the format 'HH:MM:SS'.
 * - If the time is less than one hour, returns a string in the format 'MM:SS'.
 *
 * @param seconds - The number of seconds to format.
 * @returns The formatted time string.
 */
export const formatTime = (seconds: number): string => {
    if (seconds < 0) return '00:00';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    if (hours > 0) {
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

// Converts an ISO date to YYYY-MM-DD format
export const formatDateForInput = (isoDate: string) => {
    if (!isoDate) return '';
    return isoDate.split('T')[0];
};

/**
 * Formats a given date into a string based on the specified format and locale.
 *
 * @param date - The date to format. Can be a `Date` object, a string, or a number.
 * @param format - The desired output format:
 *   - `'short'`: Returns date in `dd/mm/yyyy` format.
 *   - `'long'`: Returns date in a long format with the full month name.
 *   - `'time'`: Returns only the time in `hh:mm` format.
 *   - `'datetime'`: Returns date and time in `dd/mm/yyyy, hh:mm` format.
 *   - `'HH:mm:ss'`: Returns time in `hh:mm:ss` format.
 *   Defaults to `'short'`.
 * @param local - The locale string to use for formatting (e.g., `'fr-FR'`). Defaults to `'fr-FR'`.
 * @returns The formatted date string, or `'-'` if the input is invalid.
 */
export const formatDate = (
    date: Date | string | number,
    format: 'short' | 'long' | 'time' | 'datetime' | 'HH:mm:ss' = 'short',
    local: string = 'fr-FR',
): string => {
    const d = new Date(date);

    if (isNaN(d.getTime())) {
        return '-';
    }

    const options: Intl.DateTimeFormatOptions = {};

    switch (format) {
        case 'short':
            options.day = '2-digit';
            options.month = '2-digit';
            options.year = 'numeric';
            break;
        case 'long':
            options.day = 'numeric';
            options.month = 'long';
            options.year = 'numeric';
            break;
        case 'time':
            options.hour = '2-digit';
            options.minute = '2-digit';
            break;
        case 'datetime':
            options.day = '2-digit';
            options.month = '2-digit';
            options.year = 'numeric';
            options.hour = '2-digit';
            options.minute = '2-digit';
            break;
        case 'HH:mm:ss':
            return d.toLocaleTimeString(local, {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
    }

    return d.toLocaleDateString(local, options);
};

export const formatPercentage = (value: number, decimals: number = 1): string => {
    return `${value.toFixed(decimals)}%`;
};

/**
 * Format grade with color class
 */
export function formatGrade(score: number, total: number): { text: string; colorClass: string } {
    const limitedScore = Math.min(score, total);
    const percentage = total > 0 ? Math.round((limitedScore / total) * 100) : 0;

    let colorClass: string;
    if (percentage >= 90) colorClass = 'text-green-600';
    else if (percentage >= 70) colorClass = 'text-blue-600';
    else if (percentage >= 50) colorClass = 'text-yellow-600';
    else colorClass = 'text-red-600';

    return {
        text: `${limitedScore}/${total} (${percentage}%)`,
        colorClass,
    };
}

/**
 * Converts the first character of the given string to uppercase and the rest to lowercase.
 *
 * @param text - The string to capitalize.
 * @returns The capitalized string, or an empty string if the input is falsy.
 */
export function capitalize(text: string): string {
    if (!text) return '';
    return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
}

/**
 * Formats a number using French (France) locale conventions.
 *
 * @param value - The number to format.
 * @param locale - The locale to use for formatting (default is 'fr-FR').
 * @returns The formatted number as a string, using 'fr-FR' locale (e.g., "1 234,56").
 */
export const formatNumber = (value: number, locale: string = 'fr-FR'): string => {
    return value.toLocaleString(locale);
};

/**
 * Formats a given date as a human-readable relative time string.
 *
 * Returns phrases such as "Just now", "5 min ago", "2h ago", or "3 days ago"
 * depending on how much time has passed since the given date. If the date is more than 7 days ago,
 * it falls back to a formatted date string using `formatDate`.
 *
 * @param date - The date to format, as a `Date` object, ISO string, or timestamp.
 * @returns A relative time string representing the time elapsed since the given date.
 */
/**
 * Truncates a given string to a specified maximum length and appends an ellipsis ("...") if the string exceeds that length.
 *
 * @param text - The input string to be truncated.
 * @param maxLength - The maximum allowed length of the returned string before truncation.
 * @returns The original string if its length is less than or equal to `maxLength`, otherwise a truncated string with an appended ellipsis.
 */
export const truncateText = (text: string, maxLength: number): string => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};

/**
 * Formats a file size in bytes to a human-readable string (B, KB, MB, GB).
 */
export const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.floor(Math.log(bytes) / Math.log(1024));
    const size = bytes / Math.pow(1024, index);
    return `${size.toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
};

/**
 * Converts a UTC ISO datetime string to a local datetime-local input value (YYYY-MM-DDTHH:MM).
 * Uses the browser's local timezone so the displayed time matches the user's clock.
 */
export const toLocalDatetimeInput = (isoString: string): string => {
    const d = new Date(isoString);
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
