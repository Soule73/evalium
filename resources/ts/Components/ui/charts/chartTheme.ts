/**
 * Evalium chart theme configuration for Recharts.
 *
 * Provides a consistent color palette and shared styling constants
 * used across all chart components.
 */

export const CHART_COLORS = {
    primary: '#4f46e5',
    primaryLight: '#818cf8',
    primaryDark: '#3730a3',
    success: '#10b981',
    successLight: '#6ee7b7',
    warning: '#f59e0b',
    warningLight: '#fcd34d',
    danger: '#ef4444',
    dangerLight: '#fca5a5',
    info: '#3b82f6',
    infoLight: '#93c5fd',
    purple: '#8b5cf6',
    purpleLight: '#c4b5fd',
    neutral: '#6b7280',
    neutralLight: '#d1d5db',
} as const;

export const CHART_PALETTE = [
    CHART_COLORS.primary,
    CHART_COLORS.success,
    CHART_COLORS.warning,
    CHART_COLORS.info,
    CHART_COLORS.purple,
    CHART_COLORS.danger,
    CHART_COLORS.primaryLight,
    CHART_COLORS.successLight,
    CHART_COLORS.warningLight,
    CHART_COLORS.infoLight,
] as const;

export const SCORE_RANGE_COLORS: Record<string, string> = {
    '0-4': CHART_COLORS.danger,
    '5-8': CHART_COLORS.warning,
    '9-12': CHART_COLORS.info,
    '13-16': CHART_COLORS.success,
    '17-20': CHART_COLORS.primary,
};

export const COMPLETION_STATUS_COLORS: Record<string, string> = {
    graded: CHART_COLORS.success,
    submitted: CHART_COLORS.info,
    in_progress: CHART_COLORS.warning,
    not_started: CHART_COLORS.neutralLight,
};

export const ROLE_COLORS: Record<string, string> = {
    student: CHART_COLORS.success,
    teacher: CHART_COLORS.info,
    admin: CHART_COLORS.purple,
};

export const CHART_DEFAULTS = {
    fontSize: 12,
    fontFamily: 'inherit',
    tooltipStyle: {
        backgroundColor: '#ffffff',
        borderColor: '#e5e7eb',
        borderRadius: 8,
        boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
    },
    animationDuration: 800,
    margin: { top: 5, right: 20, bottom: 5, left: 0 },
} as const;

export const SCORE_RANGES = ['0-4', '5-8', '9-12', '13-16', '17-20'] as const;
export type ScoreRange = (typeof SCORE_RANGES)[number];
