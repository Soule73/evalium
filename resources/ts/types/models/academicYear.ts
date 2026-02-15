import type { Semester, SemesterFormData } from './semester';
import type { ClassModel } from './class';

export interface AcademicYear {
  id: number;
  name: string;
  start_date: string;
  end_date: string;
  is_current: boolean;
  created_at: string;
  updated_at: string;

  semesters?: Semester[];
  classes?: ClassModel[];
  semesters_count?: number;
  classes_count?: number;
}

export interface AcademicYearFormData {
  name: string;
  start_date: string;
  end_date: string;
  is_current?: boolean;
  semesters: SemesterFormData[];
}
