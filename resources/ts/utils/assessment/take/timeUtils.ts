/**
 * Formats time in seconds to MM:SS format
 *
 * @param seconds - Time in seconds
 * @returns Formatted time string (MM:SS)
 *
 * @example
 * formatAssessmentTime(125) // "02:05"
 * formatAssessmentTime(3661) // "61:01"
 */
export function formatAssessmentTime(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
}

/**
 * Calculates remaining time percentage
 *
 * @param timeLeft - Remaining time in seconds
 * @param duration - Total duration in minutes
 * @returns Percentage of time remaining (0-100)
 *
 * @example
 * getTimeRemainingPercentage(900, 30) // 50 (15 min remaining out of 30)
 */
export function getTimeRemainingPercentage(timeLeft: number, duration: number): number {
    const totalSeconds = duration * 60;
    return Math.max(0, Math.min(100, (timeLeft / totalSeconds) * 100));
}

/**
 * Checks if assessment time is running low (less than 10% remaining)
 *
 * @param timeLeft - Remaining time in seconds
 * @param duration - Total duration in minutes
 * @returns True if time is critical
 *
 * @example
 * isTimeCritical(120, 30) // true (2 min left out of 30)
 * isTimeCritical(1200, 30) // false (20 min left out of 30)
 */
export function isTimeCritical(timeLeft: number, duration: number): boolean {
    return getTimeRemainingPercentage(timeLeft, duration) < 10;
}

/**
 * Gets color class based on remaining time.
 *
 * - Default: neutral gray (plenty of time)
 * - Warning: amber when 1/6 or less of total time remains
 * - Danger: red when 60 seconds or fewer remain
 *
 * @param timeLeft - Remaining time in seconds
 * @param duration - Total duration in minutes
 * @returns Tailwind text color class
 */
export function getTimeColorClass(timeLeft: number, duration: number): string {
    if (timeLeft <= 60) {
        return 'text-red-600';
    }

    const totalSeconds = duration * 60;
    const warningThreshold = totalSeconds / 6;

    if (timeLeft <= warningThreshold) {
        return 'text-amber-500';
    }

    return 'text-gray-900';
}

/**
 * Returns true when the timer should pulse (last 60 seconds).
 *
 * @param timeLeft - Remaining time in seconds
 */
export function isTimePulsing(timeLeft: number): boolean {
    return timeLeft <= 60;
}

/**
 * Converts duration in minutes to seconds
 *
 * @param minutes - Duration in minutes
 * @returns Duration in seconds
 *
 * @example
 * minutesToSeconds(30) // 1800
 */
export function minutesToSeconds(minutes: number): number {
    return minutes * 60;
}

/**
 * Converts duration in seconds to minutes (rounded)
 *
 * @param seconds - Duration in seconds
 * @returns Duration in minutes
 *
 * @example
 * secondsToMinutes(1800) // 30
 * secondsToMinutes(125) // 2
 */
export function secondsToMinutes(seconds: number): number {
    return Math.floor(seconds / 60);
}

/**
 * Derives Tailwind class string for the timer display based on remaining time percentage.
 *
 * Thresholds:
 * - ≤ 10%: red, large, bold, pulsing
 * - ≤ 25%: amber, bold
 * - > 25%: neutral gray, small, semibold
 *
 * @param timeLeft - Remaining time in seconds
 * @param durationMinutes - Total assessment duration in minutes (null = no limit)
 * @returns Tailwind class string for the timer element
 */
export function getTimerClasses(timeLeft: number, durationMinutes: number | null): string {
    if (!durationMinutes || durationMinutes <= 0) {
        return 'text-gray-700 font-semibold tabular-nums';
    }
    const percent = timeLeft / (durationMinutes * 60);
    if (percent <= 0.1) {
        return 'text-red-600 text-lg font-bold tabular-nums animate-pulse';
    }
    if (percent <= 0.25) {
        return 'text-amber-500 font-bold tabular-nums';
    }
    return 'text-gray-600 text-sm font-semibold tabular-nums';
}
