import type { ClassModel } from './class';
import type { User } from './shared';

export type EnrollmentStatus = 'active' | 'withdrawn' | 'transferred' | 'completed';

export interface Enrollment {
  id: number;
  class_id: number;
  student_id: number;
  status: EnrollmentStatus;
  enrolled_at: string;
  left_date?: string;
  created_at: string;
  updated_at: string;

  class?: ClassModel;
  student?: User;

  is_active?: boolean;
}

export interface EnrollmentFormData {
  class_id: number;
  student_id: number;
  enrolled_at?: string;
}

export interface TransferStudentFormData {
  new_class_id: number;
  transfer_date?: string;
}

export interface EnrollmentWithGrades extends Enrollment {
  average_grade?: number;
  total_assessments?: number;
  completed_assessments?: number;
}
