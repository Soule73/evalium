import { useMemo } from 'react';
import { useTranslations } from './useTranslations';
import { capitalize } from '@/utils/formatting/formatters';

type TranslateFn = (key: string, replacements?: Record<string, string | number>) => string;

/**
 * Creates translated formatter functions bound to a specific translation function.
 */
function createTranslatedFormatters(t: TranslateFn) {
    const formatDuration = (minutes: number): string => {
        if (minutes <= 0) return '-';
        if (minutes < 60) return t('formatters.duration_min', { value: minutes });
        const hrs = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return mins === 0
            ? t('formatters.duration_hours', { value: hrs })
            : t('formatters.duration_hours_min', { hours: hrs, minutes: mins });
    };

    const getQuestionTypeLabel = (type: string): string => {
        const labels: Record<string, string> = {
            multiple: t('formatters.question_type_multiple'),
            one_choice: t('formatters.question_type_one_choice'),
            boolean: t('formatters.question_type_boolean'),
            text: t('formatters.question_type_text'),
            file: t('formatters.question_type_file'),
        };
        return labels[type] || type;
    };

    const getRoleLabel = (roleName: string): string => {
        const roleMap: Record<string, string> = {
            admin: t('formatters.role_admin'),
            super_admin: t('formatters.role_super_admin'),
            teacher: t('formatters.role_teacher'),
            student: t('formatters.role_student'),
        };
        return roleMap[roleName] || capitalize(roleName);
    };

    const getAssignmentBadgeLabel = (status: string): string => {
        const statusMap: Record<string, string> = {
            graded: t('formatters.assignment_graded'),
            submitted: t('formatters.assignment_submitted'),
        };
        return statusMap[status] || t('formatters.assignment_not_started');
    };

    const securityViolationLabel = (violation: string | undefined): string => {
        const violationMap: Record<string, string> = {
            tab_switch: t('formatters.security_tab_switch'),
            fullscreen_exit: t('formatters.security_fullscreen_exit'),
        };
        return violationMap[violation || ''] || t('formatters.security_violation_default');
    };

    const formatAssessmentAssignmentStatus = (status: string): { label: string; color: string } => {
        const statusMap: Record<string, { label: string; color: string }> = {
            submitted: { label: t('formatters.assignment_submitted'), color: 'info' },
            graded: { label: t('formatters.assignment_graded'), color: 'success' },
            not_assigned: { label: t('formatters.assignment_not_assigned'), color: 'gray' },
        };
        return statusMap[status] || { label: status, color: 'gray' };
    };

    return {
        formatDuration,
        getQuestionTypeLabel,
        getRoleLabel,
        getAssignmentBadgeLabel,
        securityViolationLabel,
        formatAssessmentAssignmentStatus,
    };
}

/**
 * Hook providing localized formatting functions.
 */
export function useFormatters() {
    const { t } = useTranslations();
    return useMemo(() => createTranslatedFormatters(t), [t]);
}
