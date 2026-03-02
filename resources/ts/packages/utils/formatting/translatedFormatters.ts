import { capitalize } from './formatters';

type TranslateFn = (key: string, replacements?: Record<string, string | number>) => string;

/**
 * Creates translated formatter functions bound to a specific translation function.
 *
 * @param t - Translation function from useTranslations hook
 * @returns Object with localized formatting functions
 */
export function createTranslatedFormatters(t: TranslateFn) {
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

    const securityViolationLabel = (violation: string | undefined): string => {
        const violationMap: Record<string, string> = {
            tab_switch: t('formatters.security_tab_switch'),
            fullscreen_exit: t('formatters.security_fullscreen_exit'),
        };
        return violationMap[violation || ''] || t('formatters.security_violation_default');
    };

    return {
        formatDuration,
        getQuestionTypeLabel,
        getRoleLabel,
        securityViolationLabel,
    };
}

/**
 * Formats an assessment score for display with localized labels.
 *
 * @param t - Translation function
 * @param score - Achieved score
 * @param totalPoints - Maximum possible score
 * @param isPendingReview - Whether the assessment is pending manual review
 * @param autoScore - Auto-scored portion (for partial grading)
 * @returns Formatted score string
 */
export function formatAssessmentScore(
    t: TranslateFn,
    score: number | undefined,
    totalPoints: number,
    isPendingReview?: boolean,
    autoScore?: number,
): string {
    if (isPendingReview && autoScore !== undefined) {
        return t('formatters.partial_score_mcq', { score: autoScore, total: totalPoints });
    }
    return t('formatters.score_format', { score: score || 0, total: totalPoints });
}

/**
 * Returns localized label for a boolean choice value.
 *
 * @param t - Translation function
 * @param isTrue - Boolean value to label
 * @returns Translated "True" or "False" label
 */
export function getBooleanLabel(t: TranslateFn, isTrue: boolean): string {
    return isTrue ? t('components.take_question.true') : t('components.take_question.false');
}

/**
 * Returns short localized label for a boolean choice value.
 *
 * @param t - Translation function
 * @param isTrue - Boolean value to label
 * @returns Short translated label
 */
export function getBooleanShortLabel(t: TranslateFn, isTrue: boolean): string {
    return isTrue
        ? t('components.question_result_readonly.boolean_true_short')
        : t('components.question_result_readonly.boolean_false_short');
}

/**
 * Returns localized status label text for a choice in review/result mode.
 *
 * @param t - Translation function
 * @param isSelected - Whether the choice was selected by the student
 * @param isCorrect - Whether the choice is correct
 * @param shouldShowCorrect - Whether correct answers should be revealed
 * @param isTeacherView - Whether the viewer is a teacher
 * @returns Status label text, or null if no label should be shown
 */
export function getStatusLabelText(
    t: TranslateFn,
    isSelected: boolean,
    isCorrect: boolean,
    shouldShowCorrect: boolean,
    isTeacherView: boolean,
): string | null {
    if (!shouldShowCorrect) {
        return isSelected
            ? isTeacherView
                ? t('components.question_result_readonly.student_answer')
                : t('components.question_result_readonly.your_answer')
            : null;
    }

    if (isSelected && !isCorrect) {
        return isTeacherView
            ? t('components.question_result_readonly.student_answer_incorrect')
            : t('components.question_result_readonly.your_answer_incorrect');
    }

    if (isSelected && isCorrect) {
        return isTeacherView
            ? t('components.question_result_readonly.student_answer_correct')
            : t('components.question_result_readonly.your_answer_correct');
    }

    if (!isSelected && isCorrect) {
        return t('components.question_result_readonly.correct_answer');
    }

    return null;
}
