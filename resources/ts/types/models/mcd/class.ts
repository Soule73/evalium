import type { AcademicYear } from './academicYear';
import type { Enrollment } from './enrollment';
import type { ClassSubject } from './classSubject';
import { Level, User } from '../shared';

export interface ClassModel {
  id: number;
  academic_year_id: number;
  level_id: number;
  name: string;
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
  display_name?: string;
}

export interface ClassFormData {
  academic_year_id: number;
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
