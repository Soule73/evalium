import type { ClassModel } from './class';
import type { Subject } from './subject';
import type { Semester } from './semester';
import type { User } from '../shared';
import type { Assessment } from './assessment';

export interface ClassSubject {
  id: number;
  class_id: number;
  subject_id: number;
  teacher_id: number;
  semester_id?: number;
  coefficient: number;
  valid_from: string;
  valid_to?: string;
  created_at: string;
  updated_at: string;

  class?: ClassModel;
  subject?: Subject;
  teacher?: User;
  semester?: Semester;
  assessments?: Assessment[];

  assessments_count?: number;
  is_active?: boolean;
}

export interface ClassSubjectFormData {
  class_id: number;
  subject_id: number;
  teacher_id: number;
  semester_id?: number;
  coefficient: number;
  valid_from?: string;
}

export interface ReplaceTeacherFormData {
  new_teacher_id: number;
  effective_date: string;
}

export interface UpdateCoefficientFormData {
  coefficient: number;
}

export interface ClassSubjectHistory {
  id: number;
  class_subject_id: number;
  teacher_id: number;
  valid_from: string;
  valid_to?: string;
  replaced_at?: string;
  replaced_by?: number;

  teacher?: User;
  replaced_by_user?: User;
}
