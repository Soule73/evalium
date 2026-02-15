import { type ClassSubject } from '.';
import type { AcademicYear } from './academicYear';

export interface Semester {
  id: number;
  academic_year_id: number;
  name: string;
  order_number: number;
  start_date: string;
  end_date: string;
  created_at: string;
  updated_at: string;

  academic_year?: AcademicYear;
  class_subjects?: ClassSubject[];
  class_subjects_count?: number;
}

export interface SemesterFormData {
  id?: number;
  name: string;
  start_date: string;
  end_date: string;
}
