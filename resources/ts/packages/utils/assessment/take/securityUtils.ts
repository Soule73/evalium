/**
 * Security violation types for assessment taking
 */
export const ASSESSMENT_VIOLATION_TYPES = {
    TAB_SWITCH: 'tab_switch',
    FULLSCREEN_EXIT: 'fullscreen_exit',
    DEV_TOOLS: 'dev_tools',
    COPY_PASTE: 'copy_paste',
    RIGHT_CLICK: 'right_click',
    PRINT: 'print',
    IDLE_TIMEOUT: 'idle_timeout',
    SUSPICIOUS_ACTIVITY: 'suspicious_activity',
} as const;

export type AssessmentViolationType =
    (typeof ASSESSMENT_VIOLATION_TYPES)[keyof typeof ASSESSMENT_VIOLATION_TYPES];

/**
 * Gets translation key for violation type
 *
 * @param violationType - Type of violation
 * @returns Translation key for the violation
 *
 * @example
 * getViolationTranslationKey('tab_switch') // "assessment_security.violations.tab_switch"
 */
export function getViolationTranslationKey(violationType: string): string {
    const keys: Record<string, string> = {
        [ASSESSMENT_VIOLATION_TYPES.TAB_SWITCH]: 'assessment_security.violations.tab_switch',
        [ASSESSMENT_VIOLATION_TYPES.FULLSCREEN_EXIT]:
            'assessment_security.violations.fullscreen_exit',
        [ASSESSMENT_VIOLATION_TYPES.DEV_TOOLS]: 'assessment_security.violations.dev_tools',
        [ASSESSMENT_VIOLATION_TYPES.COPY_PASTE]: 'assessment_security.violations.copy_paste',
        [ASSESSMENT_VIOLATION_TYPES.RIGHT_CLICK]: 'assessment_security.violations.right_click',
        [ASSESSMENT_VIOLATION_TYPES.PRINT]: 'assessment_security.violations.print',
        [ASSESSMENT_VIOLATION_TYPES.IDLE_TIMEOUT]: 'assessment_security.violations.idle_timeout',
        [ASSESSMENT_VIOLATION_TYPES.SUSPICIOUS_ACTIVITY]:
            'assessment_security.violations.suspicious_activity',
    };

    return keys[violationType] || 'assessment_security.violations.default';
}

/**
 * Checks if violation is critical (should terminate assessment immediately)
 *
 * @param violationType - Type of violation
 * @returns True if violation is critical
 *
 * @example
 * isCriticalViolation('tab_switch') // true
 * isCriticalViolation('right_click') // false
 */
export function isCriticalViolation(violationType: string): boolean {
    const criticalTypes: string[] = [
        ASSESSMENT_VIOLATION_TYPES.TAB_SWITCH,
        ASSESSMENT_VIOLATION_TYPES.FULLSCREEN_EXIT,
    ];
    return criticalTypes.includes(violationType);
}

/**
 * Gets severity level for violation
 *
 * @param violationType - Type of violation
 * @returns Severity level (critical, high, medium, low)
 *
 * @example
 * getViolationSeverity('tab_switch') // 'critical'
 * getViolationSeverity('right_click') // 'low'
 */
export function getViolationSeverity(
    violationType: string,
): 'critical' | 'high' | 'medium' | 'low' {
    if (isCriticalViolation(violationType)) {
        return 'critical';
    }

    const highSeverity: string[] = [
        ASSESSMENT_VIOLATION_TYPES.DEV_TOOLS,
        ASSESSMENT_VIOLATION_TYPES.COPY_PASTE,
    ];

    const mediumSeverity: string[] = [
        ASSESSMENT_VIOLATION_TYPES.PRINT,
        ASSESSMENT_VIOLATION_TYPES.IDLE_TIMEOUT,
    ];

    if (highSeverity.includes(violationType)) {
        return 'high';
    }

    if (mediumSeverity.includes(violationType)) {
        return 'medium';
    }

    return 'low';
}

/**
 * Checks if fullscreen is supported by the browser
 *
 * @returns True if fullscreen is supported
 *
 * @example
 * isFullscreenSupported() // true
 */
export function isFullscreenSupported(): boolean {
    return (
        document.fullscreenEnabled ||
        (document as unknown as Record<string, boolean>).webkitFullscreenEnabled ||
        (document as unknown as Record<string, boolean>).mozFullScreenEnabled ||
        (document as unknown as Record<string, boolean>).msFullscreenEnabled
    );
}

/**
 * Checks if currently in fullscreen mode
 *
 * @returns True if in fullscreen
 *
 * @example
 * isInFullscreen() // false
 */
export function isInFullscreen(): boolean {
    return Boolean(
        document.fullscreenElement ||
        (document as unknown as Record<string, Element | null>).webkitFullscreenElement ||
        (document as unknown as Record<string, Element | null>).mozFullScreenElement ||
        (document as unknown as Record<string, Element | null>).msFullscreenElement,
    );
}
