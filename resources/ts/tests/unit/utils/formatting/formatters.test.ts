import {
    formatTime,
    formatDateForInput,
    formatDate,
    formatDuration,
    formatPercentage,
    formatGrade,
    formatExamStatus,
    capitalize,
    getQuestionTypeLabel,
    getRoleLabel,
    getRoleColor,
    formatDeadlineWarning,
    formatUserRole,
    formatNumber,
    formatRelativeTime,
    truncateText,
    formatExamAssignmentStatus,
    canShowExamResults,
    getAssignmentStatus,
    getAssignmentStatusWithLabel,
    getStudentStatusInfo,
    getBooleanStatusInfo,
} from '@/utils/formatting/formatters';

jest.mock('@/utils/helpers/translations', () => ({
    trans: (key: string, params?: Record<string, number | string>) => {
        const translations: Record<string, string> = {
            'formatters.duration_min': `${params?.value || 0} min`,
            'formatters.duration_hours': `${params?.value || 0}h`,
            'formatters.duration_hours_min': `${params?.hours || 0}h ${params?.minutes || 0}min`,
            'formatters.deadline_minutes_remaining': `${params?.minutes || 0} minutes restantes`,
            'formatters.deadline_hours_remaining': `${params?.hours || 0} heures restantes`,
            'formatters.relative_time_minutes_ago': `Il y a ${params?.minutes || 0} min`,
            'formatters.relative_time_hours_ago': `Il y a ${params?.hours || 0}h`,
            'formatters.relative_time_days_ago': `Il y a ${params?.days || 0} jours`,
        };
        return translations[key] || key;
    },
}));

describe('formatters', () => {
    describe('formatTime', () => {
        it('should return 00:00 for negative seconds', () => {
            expect(formatTime(-10)).toBe('00:00');
        });

        it('should format seconds correctly (MM:SS)', () => {
            expect(formatTime(45)).toBe('00:45');
            expect(formatTime(90)).toBe('01:30');
            expect(formatTime(3599)).toBe('59:59');
        });

        it('should format seconds with hours (HH:MM:SS)', () => {
            expect(formatTime(3600)).toBe('01:00:00');
            expect(formatTime(3665)).toBe('01:01:05');
            expect(formatTime(7200)).toBe('02:00:00');
        });
    });

    describe('formatDateForInput', () => {
        it('should return empty string for empty input', () => {
            expect(formatDateForInput('')).toBe('');
        });

        it('should extract YYYY-MM-DD from ISO date', () => {
            expect(formatDateForInput('2025-11-07T10:30:00Z')).toBe('2025-11-07');
            expect(formatDateForInput('2024-01-15T00:00:00.000Z')).toBe('2024-01-15');
        });
    });

    describe('formatDate', () => {
        const testDate = new Date('2025-11-07T14:30:00');

        it('should format date in short format', () => {
            const result = formatDate(testDate, 'short');
            expect(result).toMatch(/07\/11\/2025/);
        });

        it('should format date in long format', () => {
            const result = formatDate(testDate, 'long');
            expect(result).toContain('novembre');
            expect(result).toContain('2025');
        });

        it('should format time only', () => {
            const result = formatDate(testDate, 'time');
            expect(result).toMatch(/14:30/);
        });

        it('should format datetime', () => {
            const result = formatDate(testDate, 'datetime');
            expect(result).toMatch(/07\/11\/2025.*14:30/);
        });

        it('should format HH:mm:ss', () => {
            const result = formatDate(testDate, 'HH:mm:ss');
            expect(result).toMatch(/14:30:00/);
        });

        it('should return - for invalid date', () => {
            expect(formatDate('invalid')).toBe('-');
            expect(formatDate('not-a-date')).toBe('-');
        });
    });

    describe('formatDuration', () => {
        it('should format minutes less than 60', () => {
            const result = formatDuration(30);
            expect(result).toContain('30');
        });

        it('should format hours', () => {
            const result = formatDuration(60);
            expect(result).toContain('1');
        });

        it('should format hours and minutes', () => {
            const result = formatDuration(90);
            expect(result).toContain('1');
            expect(result).toContain('30');
        });

        it('should handle negative values', () => {
            const result = formatDuration(-10);
            expect(result).toContain('0');
        });
    });

    describe('formatPercentage', () => {
        it('should format percentage with default decimals', () => {
            expect(formatPercentage(85.5)).toBe('85.5%');
        });

        it('should format percentage with custom decimals', () => {
            expect(formatPercentage(85.556, 2)).toBe('85.56%');
            expect(formatPercentage(85.556, 0)).toBe('86%');
        });
    });

    describe('formatGrade', () => {
        it('should format grade >= 90% as green', () => {
            const result = formatGrade(18, 20);
            expect(result.text).toBe('18/20 (90%)');
            expect(result.colorClass).toBe('text-green-600');
        });

        it('should format grade >= 70% as blue', () => {
            const result = formatGrade(14, 20);
            expect(result.text).toBe('14/20 (70%)');
            expect(result.colorClass).toBe('text-blue-600');
        });

        it('should format grade >= 50% as yellow', () => {
            const result = formatGrade(10, 20);
            expect(result.text).toBe('10/20 (50%)');
            expect(result.colorClass).toBe('text-yellow-600');
        });

        it('should format grade < 50% as red', () => {
            const result = formatGrade(5, 20);
            expect(result.text).toBe('5/20 (25%)');
            expect(result.colorClass).toBe('text-red-600');
        });

        it('should limit score to total to avoid > 100%', () => {
            const result = formatGrade(25, 20);
            expect(result.text).toBe('20/20 (100%)');
            expect(result.colorClass).toBe('text-green-600');
        });

        it('should handle zero total', () => {
            const result = formatGrade(10, 0);
            expect(result.text).toBe('0/0 (0%)');
        });
    });

    describe('formatExamStatus', () => {
        it('should return active status', () => {
            const result = formatExamStatus(true);
            expect(result).toBeTruthy();
        });

        it('should return inactive status', () => {
            const result = formatExamStatus(false);
            expect(result).toBeTruthy();
        });
    });

    describe('capitalize', () => {
        it('should capitalize first letter', () => {
            expect(capitalize('hello')).toBe('Hello');
            expect(capitalize('WORLD')).toBe('World');
        });

        it('should return empty string for empty input', () => {
            expect(capitalize('')).toBe('');
        });
    });

    describe('getQuestionTypeLabel', () => {
        it('should return label for known types', () => {
            expect(getQuestionTypeLabel('multiple')).toBeTruthy();
            expect(getQuestionTypeLabel('one_choice')).toBeTruthy();
            expect(getQuestionTypeLabel('boolean')).toBeTruthy();
            expect(getQuestionTypeLabel('text')).toBeTruthy();
        });

        it('should return original type for unknown types', () => {
            expect(getQuestionTypeLabel('unknown_type')).toBe('unknown_type');
        });
    });

    describe('getRoleLabel', () => {
        it('should return label for known roles', () => {
            expect(getRoleLabel('admin')).toBeTruthy();
            expect(getRoleLabel('teacher')).toBeTruthy();
            expect(getRoleLabel('student')).toBeTruthy();
        });

        it('should return original role for unknown roles', () => {
            expect(getRoleLabel('custom_role')).toBe('custom_role');
        });
    });

    describe('getRoleColor', () => {
        it('should return color for admin', () => {
            expect(getRoleColor('admin')).toBe('bg-red-100 text-red-800');
        });

        it('should return color for super_admin', () => {
            expect(getRoleColor('super_admin')).toBe('bg-purple-100 text-purple-800');
        });

        it('should return color for teacher', () => {
            expect(getRoleColor('teacher')).toBe('bg-blue-100 text-blue-800');
        });

        it('should return color for student', () => {
            expect(getRoleColor('student')).toBe('bg-green-100 text-green-800');
        });

        it('should return default gray for unknown role', () => {
            expect(getRoleColor('unknown')).toBe('bg-gray-100 text-gray-800');
        });
    });

    describe('formatDeadlineWarning', () => {
        it('should return high urgency for past deadline', () => {
            const pastDate = new Date(Date.now() - 1000 * 60 * 60).toISOString();
            const result = formatDeadlineWarning(pastDate);
            expect(result.urgency).toBe('high');
        });

        it('should return high urgency for less than 1 hour', () => {
            const soonDate = new Date(Date.now() + 1000 * 60 * 30).toISOString();
            const result = formatDeadlineWarning(soonDate);
            expect(result.urgency).toBe('high');
            expect(result.text).toContain('30');
        });

        it('should return high urgency for less than 24 hours', () => {
            const soonDate = new Date(Date.now() + 1000 * 60 * 60 * 5).toISOString();
            const result = formatDeadlineWarning(soonDate);
            expect(result.urgency).toBe('high');
            expect(result.text).toContain('5');
        });

        it('should return medium urgency for less than 7 days', () => {
            const futureDate = new Date(Date.now() + 1000 * 60 * 60 * 24 * 3).toISOString();
            const result = formatDeadlineWarning(futureDate);
            expect(result.urgency).toBe('medium');
        });

        it('should return low urgency for more than 7 days', () => {
            const farDate = new Date(Date.now() + 1000 * 60 * 60 * 24 * 10).toISOString();
            const result = formatDeadlineWarning(farDate);
            expect(result.urgency).toBe('low');
        });
    });

    describe('formatUserRole', () => {
        it('should format known roles', () => {
            expect(formatUserRole('admin')).toBeTruthy();
            expect(formatUserRole('teacher')).toBeTruthy();
            expect(formatUserRole('student')).toBeTruthy();
        });

        it('should capitalize unknown roles', () => {
            expect(formatUserRole('moderator')).toBe('Moderator');
        });
    });

    describe('formatNumber', () => {
        it('should format number with French locale', () => {
            expect(formatNumber(1234.56)).toContain('1');
            expect(formatNumber(1000000)).toContain('1');
        });

        it('should format number with custom locale', () => {
            const result = formatNumber(1234.56, 'en-US');
            expect(result).toContain('1');
        });
    });

    describe('formatRelativeTime', () => {
        it('should return now for very recent time', () => {
            const now = new Date();
            const result = formatRelativeTime(now);
            expect(result).toBeTruthy();
        });

        it('should return minutes ago', () => {
            const past = new Date(Date.now() - 1000 * 60 * 5);
            const result = formatRelativeTime(past);
            expect(result).toContain('5');
        });

        it('should return hours ago', () => {
            const past = new Date(Date.now() - 1000 * 60 * 60 * 2);
            const result = formatRelativeTime(past);
            expect(result).toContain('2');
        });

        it('should return days ago', () => {
            const past = new Date(Date.now() - 1000 * 60 * 60 * 24 * 3);
            const result = formatRelativeTime(past);
            expect(result).toContain('3');
        });

        it('should return formatted date for > 7 days ago', () => {
            const past = new Date(Date.now() - 1000 * 60 * 60 * 24 * 10);
            const result = formatRelativeTime(past);
            expect(result).toMatch(/\d{2}\/\d{2}\/\d{4}/);
        });
    });

    describe('truncateText', () => {
        it('should truncate long text', () => {
            const text = 'This is a very long text that needs to be truncated';
            expect(truncateText(text, 20)).toBe('This is a very long ...');
        });

        it('should not truncate short text', () => {
            const text = 'Short text';
            expect(truncateText(text, 20)).toBe('Short text');
        });

        it('should handle exact length', () => {
            const text = 'Exact';
            expect(truncateText(text, 5)).toBe('Exact');
        });
    });

    describe('formatExamAssignmentStatus', () => {
        it('should format submitted status', () => {
            const result = formatExamAssignmentStatus('submitted');
            expect(result.color).toBe('info');
            expect(result.label).toBeTruthy();
        });

        it('should format graded status', () => {
            const result = formatExamAssignmentStatus('graded');
            expect(result.color).toBe('success');
            expect(result.label).toBeTruthy();
        });

        it('should format unknown status', () => {
            const result = formatExamAssignmentStatus('unknown');
            expect(result.color).toBe('gray');
            expect(result.label).toBe('unknown');
        });
    });

    describe('canShowExamResults', () => {
        it('should return true for graded status', () => {
            expect(canShowExamResults('graded')).toBe(true);
        });

        it('should return false for other statuses', () => {
            expect(canShowExamResults('submitted')).toBe(false);
            expect(canShowExamResults('not_started')).toBe(false);
        });
    });

    describe('getAssignmentStatus', () => {
        it('should return array of status strings', () => {
            const statuses = getAssignmentStatus();
            expect(statuses).toEqual(['submitted', 'graded']);
        });
    });

    describe('getAssignmentStatusWithLabel', () => {
        it('should return array of status objects with labels', () => {
            const statuses = getAssignmentStatusWithLabel();
            expect(statuses).toHaveLength(3);
            expect(statuses[0]).toHaveProperty('value');
            expect(statuses[0]).toHaveProperty('label');
            expect(statuses.some(s => s.value === 'all')).toBe(true);
            expect(statuses.some(s => s.value === 'submitted')).toBe(true);
            expect(statuses.some(s => s.value === 'graded')).toBe(true);
        });
    });

    describe('getStudentStatusInfo', () => {
        it('should return enrolled status for active students', () => {
            const result = getStudentStatusInfo(true);
            expect(result).toEqual({ label: 'formatters.student_status_enrolled', type: 'success' });
        });

        it('should return left status for inactive students', () => {
            const result = getStudentStatusInfo(false);
            expect(result).toEqual({ label: 'formatters.student_status_left', type: 'gray' });
        });
    });

    describe('getBooleanStatusInfo', () => {
        it('should return active status for true', () => {
            const result = getBooleanStatusInfo(true);
            expect(result).toEqual({ label: 'formatters.active', type: 'success' });
        });

        it('should return inactive status for false', () => {
            const result = getBooleanStatusInfo(false);
            expect(result).toEqual({ label: 'formatters.inactive', type: 'gray' });
        });
    });

});
