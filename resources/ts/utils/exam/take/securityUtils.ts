/**
 * Security violation types for exam taking
 */
export const EXAM_VIOLATION_TYPES = {
    TAB_SWITCH: 'tab_switch',
    FULLSCREEN_EXIT: 'fullscreen_exit',
    DEV_TOOLS: 'dev_tools',
    COPY_PASTE: 'copy_paste',
    RIGHT_CLICK: 'right_click',
    PRINT: 'print',
    IDLE_TIMEOUT: 'idle_timeout',
    SUSPICIOUS_ACTIVITY: 'suspicious_activity',
} as const;

export type ExamViolationType = typeof EXAM_VIOLATION_TYPES[keyof typeof EXAM_VIOLATION_TYPES];

/**
 * Gets translation key for violation type
 * 
 * @param violationType - Type of violation
 * @returns Translation key for the violation
 * 
 * @example
 * getViolationTranslationKey('tab_switch') // "exam_security.violations.tab_switch"
 */
export function getViolationTranslationKey(violationType: string): string {
    const keys: Record<string, string> = {
        [EXAM_VIOLATION_TYPES.TAB_SWITCH]: "exam_security.violations.tab_switch",
        [EXAM_VIOLATION_TYPES.FULLSCREEN_EXIT]: "exam_security.violations.fullscreen_exit",
        [EXAM_VIOLATION_TYPES.DEV_TOOLS]: "exam_security.violations.dev_tools",
        [EXAM_VIOLATION_TYPES.COPY_PASTE]: "exam_security.violations.copy_paste",
        [EXAM_VIOLATION_TYPES.RIGHT_CLICK]: "exam_security.violations.right_click",
        [EXAM_VIOLATION_TYPES.PRINT]: "exam_security.violations.print",
        [EXAM_VIOLATION_TYPES.IDLE_TIMEOUT]: "exam_security.violations.idle_timeout",
        [EXAM_VIOLATION_TYPES.SUSPICIOUS_ACTIVITY]: "exam_security.violations.suspicious_activity",
    };

    return keys[violationType] || "exam_security.violations.default";
}

/**
 * Checks if violation is critical (should terminate exam immediately)
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
        EXAM_VIOLATION_TYPES.TAB_SWITCH,
        EXAM_VIOLATION_TYPES.FULLSCREEN_EXIT,
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
export function getViolationSeverity(violationType: string): 'critical' | 'high' | 'medium' | 'low' {
    if (isCriticalViolation(violationType)) {
        return 'critical';
    }

    const highSeverity: string[] = [
        EXAM_VIOLATION_TYPES.DEV_TOOLS,
        EXAM_VIOLATION_TYPES.COPY_PASTE,
    ];

    const mediumSeverity: string[] = [
        EXAM_VIOLATION_TYPES.PRINT,
        EXAM_VIOLATION_TYPES.IDLE_TIMEOUT,
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
        (document as any).webkitFullscreenEnabled ||
        (document as any).mozFullScreenEnabled ||
        (document as any).msFullscreenEnabled
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
        (document as any).webkitFullscreenElement ||
        (document as any).mozFullScreenElement ||
        (document as any).msFullscreenElement
    );
}
