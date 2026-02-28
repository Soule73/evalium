type BadgeType = 'success' | 'error' | 'warning' | 'info' | 'gray';
type TranslateFn = (key: string) => string;

export interface AssignmentStatusInfo {
    label: string;
    badgeType: BadgeType;
}

interface ResolveStatusOptions {
    isVirtual?: boolean;
    submittedAt?: string | null;
    score?: number | null;
    assessmentHasEnded?: boolean;
}

/**
 * Resolves assignment display status for teacher-side views.
 *
 * Uses runtime state fields (is_virtual, submitted_at, score, assessmentHasEnded)
 * to determine the most accurate display status. This is the single source
 * of truth for all teacher-facing assignment status badges.
 */
export function resolveAssignmentDisplayStatus(
    t: TranslateFn,
    options: ResolveStatusOptions,
): AssignmentStatusInfo {
    const { isVirtual, submittedAt, score, assessmentHasEnded } = options;

    if (isVirtual) {
        return { label: t('formatters.assignment_status_not_started'), badgeType: 'gray' };
    }

    if (!submittedAt && assessmentHasEnded) {
        return { label: t('formatters.assignment_status_not_submitted'), badgeType: 'error' };
    }

    if (!submittedAt) {
        return { label: t('formatters.assignment_status_in_progress'), badgeType: 'info' };
    }

    if (score === null || score === undefined) {
        return { label: t('formatters.assignment_status_pending_grading'), badgeType: 'warning' };
    }

    return { label: t('formatters.assignment_status_graded'), badgeType: 'success' };
}

/**
 * Formats assignment status from the backend status field.
 *
 * Used in student-facing views and any context where the status
 * is already resolved server-side as a string enum value.
 */
export function formatAssignmentStatus(t: TranslateFn, status: string): AssignmentStatusInfo {
    const statusMap: Record<string, AssignmentStatusInfo> = {
        not_submitted: { label: t('formatters.assignment_status_not_started'), badgeType: 'gray' },
        in_progress: { label: t('formatters.assignment_status_in_progress'), badgeType: 'info' },
        submitted: { label: t('formatters.assignment_status_submitted'), badgeType: 'success' },
        graded: { label: t('formatters.assignment_status_graded'), badgeType: 'success' },
        not_assigned: { label: t('formatters.assignment_status_not_assigned'), badgeType: 'gray' },
    };

    return statusMap[status] ?? { label: status, badgeType: 'gray' };
}
