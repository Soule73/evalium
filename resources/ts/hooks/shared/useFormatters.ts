import { useMemo } from 'react';
import { useTranslations } from './useTranslations';
import { type BadgeType } from '@/Components/ui/Badge/Badge';
import { capitalize, formatDate } from '@/utils/formatting/formatters';

type TranslateFn = (key: string, replacements?: Record<string, string | number>) => string;

/**
 * Creates translated formatter functions bound to a specific translation function.
 */
function createTranslatedFormatters(t: TranslateFn) {
  const formatDuration = (minutes: number): string => {
    if (minutes < 0) return t('formatters.duration_min', { value: 0 });
    if (minutes < 60) return t('formatters.duration_min', { value: minutes });
    const hrs = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return mins === 0
      ? t('formatters.duration_hours', { value: hrs })
      : t('formatters.duration_hours_min', { hours: hrs, minutes: mins });
  };

  const formatAssessmentStatus = (status: boolean): string => {
    return status
      ? t('formatters.assessment_status_active')
      : t('formatters.assessment_status_inactive');
  };

  const getQuestionTypeLabel = (type: string): string => {
    const labels: Record<string, string> = {
      'multiple': t('formatters.question_type_multiple'),
      'one_choice': t('formatters.question_type_one_choice'),
      'boolean': t('formatters.question_type_boolean'),
      'text': t('formatters.question_type_text'),
    };
    return labels[type] || type;
  };

  const getRoleLabel = (roleName: string): string => {
    const roleMap: Record<string, string> = {
      'admin': t('formatters.role_admin'),
      'super_admin': t('formatters.role_super_admin'),
      'teacher': t('formatters.role_teacher'),
      'student': t('formatters.role_student'),
    };
    return roleMap[roleName] || roleName;
  };

  const getAssignmentBadgeLabel = (status: string): string => {
    const statusMap: Record<string, string> = {
      'graded': t('formatters.assignment_graded'),
      'submitted': t('formatters.assignment_submitted'),
    };
    return statusMap[status] || t('formatters.assignment_not_started');
  };

  const securityViolationLabel = (violation: string | undefined): string => {
    const violationMap: Record<string, string> = {
      'tab_switch': t('formatters.security_tab_switch'),
      'fullscreen_exit': t('formatters.security_fullscreen_exit'),
    };
    return violationMap[violation || ''] || t('formatters.security_violation_default');
  };

  const getAssignmentStatusLabels = (): Record<string, string> => ({
    submitted: t('formatters.assignment_submitted'),
    graded: t('formatters.assignment_graded'),
    default: t('formatters.assignment_not_started'),
  });

  const formatDeadlineWarning = (endDate: string): { text: string; urgency: 'low' | 'medium' | 'high' } => {
    const end = new Date(endDate);
    const now = new Date();
    const diff = end.getTime() - now.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);

    if (diff <= 0) {
      return { text: t('formatters.deadline_assessment_finished'), urgency: 'high' };
    } else if (hours < 1) {
      const minutes = Math.floor(diff / (1000 * 60));
      return { text: t('formatters.deadline_minutes_remaining', { minutes }), urgency: 'high' };
    } else if (hours < 24) {
      return { text: t('formatters.deadline_hours_remaining', { hours }), urgency: 'high' };
    } else if (days < 7) {
      return {
        text: days === 1
          ? t('formatters.deadline_day_remaining', { days })
          : t('formatters.deadline_days_remaining', { days }),
        urgency: 'medium',
      };
    } else {
      return { text: t('formatters.deadline_days_remaining', { days }), urgency: 'low' };
    }
  };

  const formatUserRole = (role: string): string => {
    const roleMap: Record<string, string> = {
      'admin': t('formatters.role_admin'),
      'teacher': t('formatters.role_teacher'),
      'student': t('formatters.role_student'),
    };
    return roleMap[role] || capitalize(role);
  };

  const formatRelativeTime = (date: Date | string | number): string => {
    const now = new Date();
    const target = new Date(date);
    const diffMs = now.getTime() - target.getTime();
    const diffMinutes = Math.floor(diffMs / (1000 * 60));
    const diffHours = Math.floor(diffMinutes / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMinutes < 1) return t('formatters.relative_time_now');
    if (diffMinutes < 60) return t('formatters.relative_time_minutes_ago', { minutes: diffMinutes });
    if (diffHours < 24) return t('formatters.relative_time_hours_ago', { hours: diffHours });
    if (diffDays < 7) return t('formatters.relative_time_days_ago', { days: diffDays });
    return formatDate(target, 'short');
  };

  const formatAssessmentAssignmentStatus = (status: string): { label: string; color: string } => {
    const statusMap: Record<string, { label: string; color: string }> = {
      'submitted': { label: t('formatters.assignment_submitted'), color: 'info' },
      'graded': { label: t('formatters.assignment_graded'), color: 'success' },
      'not_assigned': { label: t('formatters.assignment_not_assigned'), color: 'gray' },
    };
    return statusMap[status] || { label: status, color: 'gray' };
  };

  const getAssignmentStatusWithLabel = (): Array<{ value: string; label: string }> => {
    return [
      { value: 'all', label: t('formatters.assignment_all_statuses') },
      { value: 'submitted', label: t('formatters.assignment_submitted') },
      { value: 'graded', label: t('formatters.assignment_graded') },
    ];
  };

  const getStudentStatusInfo = (isActive: boolean): { label: string; type: BadgeType } => {
    return isActive
      ? { label: t('formatters.student_status_enrolled'), type: 'success' }
      : { label: t('formatters.student_status_left'), type: 'gray' };
  };

  const getBooleanStatusInfo = (isActive: boolean): { label: string; type: BadgeType } => {
    return isActive
      ? { label: t('formatters.active'), type: 'success' }
      : { label: t('formatters.inactive'), type: 'gray' };
  };

  return {
    formatDuration,
    formatAssessmentStatus,
    getQuestionTypeLabel,
    getRoleLabel,
    getAssignmentBadgeLabel,
    securityViolationLabel,
    getAssignmentStatusLabels,
    formatDeadlineWarning,
    formatUserRole,
    formatRelativeTime,
    formatAssessmentAssignmentStatus,
    getAssignmentStatusWithLabel,
    getStudentStatusInfo,
    getBooleanStatusInfo,
  };
}

/**
 * Hook providing localized formatting functions.
 */
export function useFormatters() {
  const { t } = useTranslations();
  return useMemo(() => createTranslatedFormatters(t), [t]);
}
