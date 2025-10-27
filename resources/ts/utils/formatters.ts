
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
    if (minutes < 0) return '0 min';
    if (minutes < 60) {
        return `${minutes} min`;
    }
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins === 0 ? `${hrs}h` : `${hrs}h ${mins}min`;
};
// Formatage des pourcentages
export const formatPercentage = (value: number, decimals: number = 1): string => {
    return `${value.toFixed(decimals)}%`;
};

/**
 * Format grade with color class - cohérent avec useExamScoring
 */
export function formatGrade(score: number, total: number): { text: string; colorClass: string } {
    // Limitation du score pour éviter les pourcentages > 100%
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
 * Formats the exam status as a human-readable string.
 *
 * @param status - The boolean status of the exam. `true` indicates active, `false` indicates inactive.
 * @returns A string representing the exam status: 'Actif' if active, 'Inactif' if inactive.
 */
export function formatExamStatus(status: boolean): string {

    return status ? 'Actif' : 'Inactif';
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
    const labels = {
        'multiple': 'Choix multiples',
        'one_choice': 'Choix unique',
        'boolean': 'Vrai/Faux',
        'text': 'Réponse libre'
    };
    return labels[type as keyof typeof labels] || type;
};

export const getRoleLabel = (roleName: string) => {
    switch (roleName) {
        case 'admin':
            return 'Administrateur';
        case 'teacher':
            return 'Enseignant';
        case 'student':
            return 'Étudiant';
        default:
            return roleName;
    }
};

export const getRoleColor = (roleName: string) => {
    switch (roleName) {
        case 'admin':
            return 'bg-red-100 text-red-800';
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
        case 'pending_review': return 'warning';
        default: return 'error';
    }
};

export const getAssignmentBadgeLabel = (status: string) => {
    switch (status) {
        case 'graded': return 'Noté';
        case 'submitted': return 'Soumis';
        case 'pending_review': return 'En attente de révision';
        case 'started': return 'Commencé';
        case 'assigned': return 'Assigné';
        default: return 'Non commencé';
    }
};

export const securityViolationLabel = (violation: string | undefined): string => {
    switch (violation) {
        case 'tab_switch':
            return "Changement d'onglet détecté";
        case 'fullscreen_exit':
            return "Sortie du mode plein écran détectée";
        default:
            return "Violation de sécurité détectée";
    }
}

export const assignmentStatusColors: Record<string, string> = {
    assigned: 'bg-yellow-100 text-yellow-800',
    started: 'bg-blue-100 text-blue-800',
    submitted: 'bg-green-100 text-green-800',
    graded: 'bg-purple-100 text-purple-800',
    pending_review: 'bg-orange-100 text-orange-800',
    default: 'bg-gray-100 text-gray-800'
};

export const assignmentStatusLabels: Record<string, string> = {
    assigned: 'À faire',
    started: 'En cours',
    submitted: 'Soumis',
    graded: 'Noté',
    pending_review: 'En révision',
    default: 'Non commencé'
};


/**
 * Format exam deadline warning
 */
/**
 * Formats a warning message and urgency level based on the remaining time until a given end date.
 *
 * @param endDate - The ISO string representing the deadline to compare against the current time.
 * @returns An object containing a human-readable warning text and an urgency level:
 * - If the deadline has passed, returns "Examen terminé" with 'high' urgency.
 * - If less than 1 hour remains, returns the number of minutes left with 'high' urgency.
 * - If less than 24 hours remain, returns the number of hours left with 'high' urgency.
 * - If less than 7 days remain, returns the number of days left with 'medium' urgency.
 * - Otherwise, returns the number of days left with 'low' urgency.
 */
export function formatDeadlineWarning(endDate: string): { text: string; urgency: 'low' | 'medium' | 'high' } {
    const end = new Date(endDate);
    const now = new Date();
    const diff = end.getTime() - now.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);

    if (diff <= 0) {
        return { text: 'Examen terminé', urgency: 'high' };
    } else if (hours < 1) {
        const minutes = Math.floor(diff / (1000 * 60));
        return { text: `${minutes} minutes restantes`, urgency: 'high' };
    } else if (hours < 24) {
        return { text: `${hours} heures restantes`, urgency: 'high' };
    } else if (days < 7) {
        return { text: `${days} jours restants`, urgency: 'medium' };
    } else {
        return { text: `${days} jours restants`, urgency: 'low' };
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
        'admin': 'Administrateur',
        'teacher': 'Enseignant',
        'student': 'Étudiant',
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
        return "À l'instant";
    } else if (diffMinutes < 60) {
        return `Il y a ${diffMinutes} min`;
    } else if (diffHours < 24) {
        return `Il y a ${diffHours}h`;
    } else if (diffDays < 7) {
        return `Il y a ${diffDays} jour${diffDays > 1 ? 's' : ''}`;
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
 * Returns a formatted label and color for a given exam assignment status.
 *
 * Maps known status strings to their corresponding French label and color.
 * If the status is not recognized, returns the status as the label and 'gray' as the color.
 *
 * @param status - The status string of the exam assignment (e.g., 'assigned', 'started', 'submitted', 'pending_review', 'graded').
 * @returns An object containing the `label` (string) and `color` (string) for the given status.
 */
export const formatExamAssignmentStatus = (status: string): { label: string; color: string } => {
    const statusMap = {
        'assigned': { label: 'Assigné', color: 'info' },
        'started': { label: 'Commencé', color: 'warning' },
        'submitted': { label: 'Soumis', color: 'info' },
        'pending_review': { label: 'En attente de révision', color: 'warning' },
        'graded': { label: 'Noté', color: 'success' },
    };

    return statusMap[status as keyof typeof statusMap] || { label: status, color: 'gray' };
};

export const canShowExamResults = (assignmentStatus: string): boolean => {
    return assignmentStatus === 'graded';
}

/**
 * Returns an array of possible assignment status strings.
 *
 * @returns {string[]} An array containing the assignment statuses: 
 * 'assigned', 'started', 'submitted', 'pending_review', and 'graded'.
 */
export const getAssignmentStatus = () => {
    return ['assigned', 'started', 'submitted', 'pending_review', 'graded'];
};

/**
 * Returns an array of assignment status objects, each containing a `value` and a `label`.
 * The labels are provided in French and represent different statuses an assignment can have.
 *
 * @returns {Array<{ value: string; label: string }>} An array of status objects for assignments.
 *
 * Status values include:
 * - 'all': All statuses
 * - 'assigned': Assigned
 * - 'started':  Started
 * - 'submitted':  Submitted
 * - 'pending_review': Pending review
 * - 'graded': Graded
 */
export const getAssignmentStatusWithLabel = (): Array<{ value: string; label: string; }> => {
    return [
        { value: 'all', label: 'Tous les statuts' },
        { value: 'assigned', label: 'Assigné' },
        { value: 'started', label: 'Commencé' },
        { value: 'submitted', label: 'Soumis' },
        { value: 'pending_review', label: 'En attente de révision' },
        { value: 'graded', label: 'Noté' },
    ];
};
