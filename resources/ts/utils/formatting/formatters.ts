
import { BadgeType } from '@/Components/ui/Badge/Badge';
import { trans } from '../helpers/translations';


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


// Fonction pour convertir la date ISO en format YYYY-MM-DD
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
    local: string = 'fr-FR'
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
                second: '2-digit'
            });
    }

    return d.toLocaleDateString(local, options);
};

// Formatage des durées en texte lisible
export const formatDuration = (minutes: number): string => {
    if (minutes < 0) return trans('formatters.duration_min', { value: 0 });
    if (minutes < 60) {
        return trans('formatters.duration_min', { value: minutes });
    }
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins === 0
        ? trans('formatters.duration_hours', { value: hrs })
        : trans('formatters.duration_hours_min', { hours: hrs, minutes: mins });
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

    let colorClass = '';
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
 * Formats the assessment status as a human-readable string.
 *
 * @param status - The boolean status of the assessment. `true` indicates active, `false` indicates inactive.
 * @returns A string representing the assessment status: 'Actif' if active, 'Inactif' if inactive.
 */
export function formatAssessmentStatus(status: boolean): string {
    return status
        ? trans('formatters.assessment_status_active')
        : trans('formatters.assessment_status_inactive');
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

export const getQuestionTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        'multiple': trans('formatters.question_type_multiple'),
        'one_choice': trans('formatters.question_type_one_choice'),
        'boolean': trans('formatters.question_type_boolean'),
        'text': trans('formatters.question_type_text')
    };
    return labels[type] || type;
};

export const getRoleLabel = (roleName: string) => {
    const roleMap: Record<string, string> = {
        'admin': trans('formatters.role_admin'),
        'super_admin': trans('formatters.role_super_admin'),
        'teacher': trans('formatters.role_teacher'),
        'student': trans('formatters.role_student')
    };
    return roleMap[roleName] || roleName;
};

export const getRoleColor = (roleName: string) => {
    switch (roleName) {
        case 'admin':
            return 'bg-red-100 text-red-800';
        case 'super_admin':
            return 'bg-purple-100 text-purple-800';
        case 'teacher':
            return 'bg-blue-100 text-blue-800';
        case 'student':
            return 'bg-green-100 text-green-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

export const getAssignmentBadgeType = (status: string) => {
    switch (status) {
        case 'graded': return 'success';
        case 'submitted': return 'info';
        default: return 'error';
    }
};

export const getAssignmentBadgeLabel = (status: string) => {
    const statusMap: Record<string, string> = {
        'graded': trans('formatters.assignment_graded'),
        'submitted': trans('formatters.assignment_submitted')
    };
    return statusMap[status] || trans('formatters.assignment_not_started');
};

export const securityViolationLabel = (violation: string | undefined): string => {
    const violationMap: Record<string, string> = {
        'tab_switch': trans('formatters.security_tab_switch'),
        'fullscreen_exit': trans('formatters.security_fullscreen_exit')
    };
    return violationMap[violation || ''] || trans('formatters.security_violation_default');
}

export const assignmentStatusColors: Record<string, string> = {
    submitted: 'bg-green-100 text-green-800',
    graded: 'bg-purple-100 text-purple-800',
    default: 'bg-gray-100 text-gray-800'
};

export const getAssignmentStatusLabels = (): Record<string, string> => ({
    submitted: trans('formatters.assignment_submitted'),
    graded: trans('formatters.assignment_graded'),
    default: trans('formatters.assignment_not_started')
});


/**
 * Formats a deadline warning message based on the time remaining until the given end date. 
 */
export function formatDeadlineWarning(endDate: string): { text: string; urgency: 'low' | 'medium' | 'high' } {
    const end = new Date(endDate);
    const now = new Date();
    const diff = end.getTime() - now.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);

    if (diff <= 0) {
        return { text: trans('formatters.deadline_assessment_finished'), urgency: 'high' };
    } else if (hours < 1) {
        const minutes = Math.floor(diff / (1000 * 60));
        return { text: trans('formatters.deadline_minutes_remaining', { minutes }), urgency: 'high' };
    } else if (hours < 24) {
        return { text: trans('formatters.deadline_hours_remaining', { hours }), urgency: 'high' };
    } else if (days < 7) {
        return {
            text: days === 1
                ? trans('formatters.deadline_day_remaining', { days })
                : trans('formatters.deadline_days_remaining', { days }),
            urgency: 'medium'
        };
    } else {
        return { text: trans('formatters.deadline_days_remaining', { days }), urgency: 'low' };
    }
}

/**
 * Formats a user role string into a human-readable label.
 *
 * Maps known role identifiers ('admin', 'teacher', 'student') to their corresponding
 * French labels. If the role is not recognized, it returns the capitalized version
 * of the input role string.
 *
 * @param role - The role identifier to format.
 * @returns The formatted, human-readable role label.
 */
export function formatUserRole(role: string): string {
    const roleMap: Record<string, string> = {
        'admin': trans('formatters.role_admin'),
        'teacher': trans('formatters.role_teacher'),
        'student': trans('formatters.role_student'),
    };

    return roleMap[role] || capitalize(role);
}

/**
 * Formats a number using French (France) locale conventions.
 *
 * @param value - The number to format.
 * @param locale - The locale to use for formatting (default is 'fr-FR').
 * @returns The formatted number as a string, using 'fr-FR' locale (e.g., "1 234,56").
 */
export const formatNumber = (value: number, locale: string = 'fr-FR'): string => {
    return value.toLocaleString(locale);
};

/**
 * Formats a given date as a human-readable relative time string in French.
 *
 * Returns phrases such as "À l'instant", "Il y a 5 min", "Il y a 2h", or "Il y a 3 jours"
 * depending on how much time has passed since the given date. If the date is more than 7 days ago,
 * it falls back to a formatted date string using `formatDate`.
 *
 * @param date - The date to format, as a `Date` object, ISO string, or timestamp.
 * @returns A French relative time string representing the time elapsed since the given date.
 */
export const formatRelativeTime = (date: Date | string | number): string => {
    const now = new Date();
    const target = new Date(date);
    const diffMs = now.getTime() - target.getTime();
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMinutes < 1) {
        return trans('formatters.relative_time_now');
    } else if (diffMinutes < 60) {
        return trans('formatters.relative_time_minutes_ago', { minutes: diffMinutes });
    } else if (diffHours < 24) {
        return trans('formatters.relative_time_hours_ago', { hours: diffHours });
    } else if (diffDays < 7) {
        return trans('formatters.relative_time_days_ago', { days: diffDays });
    } else {
        return formatDate(target, 'short');
    }
};

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
 * Returns a formatted label and color for a given assessment assignment status.
 *
 * Maps known status strings to their corresponding French label and color.
 * If the status is not recognized, returns the status as the label and 'gray' as the color.
 *
 * @param status - The status string of the assessment assignment (e.g., 'submitted', 'graded').
 * @returns An object containing the `label` (string) and `color` (string) for the given status.
 */
export const formatAssessmentAssignmentStatus = (status: string): { label: string; color: string } => {
    const statusMap: Record<string, { label: string; color: string }> = {
        'submitted': { label: trans('formatters.assignment_submitted'), color: 'info' },
        'graded': { label: trans('formatters.assignment_graded'), color: 'success' },
        'not_assigned': { label: trans('formatters.assignment_not_assigned'), color: 'gray' }
    };

    return statusMap[status] || { label: status, color: 'gray' };
};

export const canShowAssessmentResults = (assignmentStatus: string, showResultsImmediately: boolean = false): boolean => {
    if (showResultsImmediately && (assignmentStatus === 'submitted' || assignmentStatus === 'graded')) {
        return true;
    }
    return assignmentStatus === 'graded';
}

/**
 * Returns an array of possible assignment status strings.
 *
 * @returns {string[]} An array containing the assignment statuses: 
 * 'submitted' and 'graded'.
 */
export const getAssignmentStatus = () => {
    return ['submitted', 'graded'];
};

/**
 * Returns an array of assignment status objects, each containing a `value` and a `label`.
 * The labels are provided in French and represent different statuses an assignment can have.
 *
 * @returns {Array<{ value: string; label: string }>} An array of status objects for assignments.
 *
 * Status values include:
 * - 'all': All statuses
 * - 'submitted':  Submitted
 * - 'graded': Graded
 */
export const getAssignmentStatusWithLabel = (): Array<{ value: string; label: string; }> => {
    return [
        { value: 'all', label: trans('formatters.assignment_all_statuses') },
        { value: 'submitted', label: trans('formatters.assignment_submitted') },
        { value: 'graded', label: trans('formatters.assignment_graded') },
    ];
};

/**
 * Returns the status information for a student based on their active status.
 * @param isActive - A boolean indicating whether the student is currently active (enrolled) or not.
 * @returns An object containing a `label` and a `type` for the student's status.
 *          If the student is active, the label will indicate they are enrolled and the type will be 'success'.
 *          If the student is not active, the label will indicate they have left and the type will be 'gray'.
 */
export const getStudentStatusInfo = (isActive: boolean): { label: string; type: BadgeType } => {
    return isActive
        ? { label: trans('formatters.student_status_enrolled'), type: 'success' }
        : { label: trans('formatters.student_status_left'), type: 'gray' };
};

export const getBooleanStatusInfo = (isActive: boolean): { label: string; type: BadgeType } => {
    return isActive
        ? { label: trans('formatters.active'), type: 'success' }
        : { label: trans('formatters.inactive'), type: 'gray' };
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
