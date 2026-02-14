import { describe, it, expect } from 'vitest';
import {
    formatTime,
    formatDateForInput,
    formatDate,
    formatPercentage,
    formatGrade,
    capitalize,
    formatNumber,
    truncateText,
} from './formatters';

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
            expect(result).toContain('2025');
        });

        it('should format date with time', () => {
            const result = formatDate(testDate, 'datetime');
            expect(result).toContain('14:30');
        });
    });

    describe('formatPercentage', () => {
        it('should format percentage with decimals', () => {
            expect(formatPercentage(75.5)).toBe('75.5%');
            expect(formatPercentage(100)).toBe('100.0%');
            expect(formatPercentage(0)).toBe('0.0%');
        });
    });

    describe('formatGrade', () => {
        it('should return grade object with text and colorClass', () => {
            const result = formatGrade(15.5, 20);
            expect(result).toEqual({
                text: '15.5/20 (78%)',
                colorClass: 'text-blue-600'
            });
        });

        it('should handle perfect score', () => {
            const result = formatGrade(20, 20);
            expect(result).toEqual({
                text: '20/20 (100%)',
                colorClass: 'text-green-600'
            });
        });

        it('should handle zero score', () => {
            const result = formatGrade(0, 20);
            expect(result).toEqual({
                text: '0/20 (0%)',
                colorClass: 'text-red-600'
            });
        });
    });

    describe('capitalize', () => {
        it('should capitalize first letter and lowercase rest', () => {
            expect(capitalize('hello')).toBe('Hello');
            expect(capitalize('WORLD')).toBe('World');
            expect(capitalize('')).toBe('');
        });
    });

    describe('truncateText', () => {
        it('should truncate long text', () => {
            const longText = 'This is a very long text that should be truncated';
            expect(truncateText(longText, 20)).toBe('This is a very long ...');
        });

        it('should not truncate short text', () => {
            expect(truncateText('Short', 20)).toBe('Short');
        });
    });

    describe('formatNumber', () => {
        it('should format numbers with locale', () => {
            expect(formatNumber(1000)).toContain('1');
            expect(formatNumber(1234567)).toContain('1');
        });
    });
});
