import type { AcademicYear } from './academicYear';
import type { Enrollment } from './enrollment';
import type { ClassSubject } from './classSubject';
import { type Level, type User } from './shared';

export interface ClassModel {
    id: number;
    academic_year_id: number;
    level_id: number;
    name: string;
    description?: string;
    max_students: number;
    created_at: string;
    updated_at: string;

    academic_year?: AcademicYear;
    level?: Level;
    enrollments?: Enrollment[];
    class_subjects?: ClassSubject[];
    students?: User[];

    enrollments_count?: number;
    active_enrollments_count?: number;
    subjects_count?: number;
    can_delete?: boolean;
}

export interface ClassFormData {
    level_id: number;
    name: string;
    max_students: number;
}

export interface ClassStatistics {
    total_students: number;
    active_students: number;
    withdrawn_students: number;
    subjects_count: number;
    assessments_count: number;
}
