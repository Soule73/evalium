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
 * Gets color class based on remaining time percentage
 * 
 * @param timeLeft - Remaining time in seconds
 * @param duration - Total duration in minutes
 * @returns Tailwind color class
 * 
 * @example
 * getTimeColorClass(900, 30) // "text-yellow-600"
 * getTimeColorClass(120, 30) // "text-red-600"
 */
export function getTimeColorClass(timeLeft: number, duration: number): string {
    const percentage = getTimeRemainingPercentage(timeLeft, duration);

    if (percentage < 10) {
        return 'text-red-600';
    } else if (percentage < 25) {
        return 'text-yellow-600';
    }

    return 'text-green-600';
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
