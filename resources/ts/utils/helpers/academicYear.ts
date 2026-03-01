import type { AcademicYear } from '@/types';

export type AcademicYearStatusType = 'current' | 'archived' | 'future';

/**
 * Determines the display status of an academic year based on its dates and current flag.
 */
export const getAcademicYearStatus = (year: AcademicYear): AcademicYearStatusType => {
    if (year.is_current) return 'current';
    if (new Date(year.end_date) < new Date()) return 'archived';
    return 'future';
};
