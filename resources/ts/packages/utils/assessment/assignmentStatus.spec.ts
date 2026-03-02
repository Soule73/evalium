import { describe, it, expect } from 'vitest';
import { resolveAssignmentDisplayStatus, formatAssignmentStatus } from './assignmentStatus';

const mockT = (key: string): string => key;

describe('resolveAssignmentDisplayStatus', () => {
    it('returns not_started for virtual assignments', () => {
        const result = resolveAssignmentDisplayStatus(mockT, { isVirtual: true });
        expect(result.label).toBe('formatters.assignment_status_not_started');
        expect(result.badgeType).toBe('gray');
    });

    it('returns not_submitted when assessment ended without submission', () => {
        const result = resolveAssignmentDisplayStatus(mockT, {
            assessmentHasEnded: true,
            submittedAt: null,
        });
        expect(result.label).toBe('formatters.assignment_status_not_submitted');
        expect(result.badgeType).toBe('error');
    });

    it('returns in_progress when not submitted and assessment still running', () => {
        const result = resolveAssignmentDisplayStatus(mockT, {
            submittedAt: null,
            assessmentHasEnded: false,
        });
        expect(result.label).toBe('formatters.assignment_status_in_progress');
        expect(result.badgeType).toBe('info');
    });

    it('returns pending_grading when submitted but score is null', () => {
        const result = resolveAssignmentDisplayStatus(mockT, {
            submittedAt: '2024-01-01T00:00:00Z',
            score: null,
        });
        expect(result.label).toBe('formatters.assignment_status_pending_grading');
        expect(result.badgeType).toBe('warning');
    });

    it('returns pending_grading when submitted but score is undefined', () => {
        const result = resolveAssignmentDisplayStatus(mockT, {
            submittedAt: '2024-01-01T00:00:00Z',
            score: undefined,
        });
        expect(result.label).toBe('formatters.assignment_status_pending_grading');
        expect(result.badgeType).toBe('warning');
    });

    it('returns graded when submitted with a score', () => {
        const result = resolveAssignmentDisplayStatus(mockT, {
            submittedAt: '2024-01-01T00:00:00Z',
            score: 15,
        });
        expect(result.label).toBe('formatters.assignment_status_graded');
        expect(result.badgeType).toBe('success');
    });

    it('returns graded when score is 0', () => {
        const result = resolveAssignmentDisplayStatus(mockT, {
            submittedAt: '2024-01-01T00:00:00Z',
            score: 0,
        });
        expect(result.label).toBe('formatters.assignment_status_graded');
        expect(result.badgeType).toBe('success');
    });
});

describe('formatAssignmentStatus', () => {
    it('maps not_submitted to not_started with gray badge', () => {
        const result = formatAssignmentStatus(mockT, 'not_submitted');
        expect(result.label).toBe('formatters.assignment_status_not_started');
        expect(result.badgeType).toBe('gray');
    });

    it('maps in_progress to info badge', () => {
        const result = formatAssignmentStatus(mockT, 'in_progress');
        expect(result.label).toBe('formatters.assignment_status_in_progress');
        expect(result.badgeType).toBe('info');
    });

    it('maps submitted to success badge', () => {
        const result = formatAssignmentStatus(mockT, 'submitted');
        expect(result.label).toBe('formatters.assignment_status_submitted');
        expect(result.badgeType).toBe('success');
    });

    it('maps graded to success badge', () => {
        const result = formatAssignmentStatus(mockT, 'graded');
        expect(result.label).toBe('formatters.assignment_status_graded');
        expect(result.badgeType).toBe('success');
    });

    it('maps not_assigned to gray badge', () => {
        const result = formatAssignmentStatus(mockT, 'not_assigned');
        expect(result.label).toBe('formatters.assignment_status_not_assigned');
        expect(result.badgeType).toBe('gray');
    });

    it('returns raw status string for unknown statuses', () => {
        const result = formatAssignmentStatus(mockT, 'unknown_status');
        expect(result.label).toBe('unknown_status');
        expect(result.badgeType).toBe('gray');
    });
});
