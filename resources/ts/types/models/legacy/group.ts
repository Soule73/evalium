import type { Level } from '../shared/level';
import type { User } from '../shared/user';
import type { Exam } from './exam';

export interface Group {
  id: number;
  display_name: string;
  description?: string;
  level_id: number | null;
  level?: Level;
  start_date: string;
  end_date: string;
  max_students: number;
  is_active: boolean;
  academic_year?: string;
  created_at: string;
  updated_at: string;
  active_students?: User[];
  active_students_count?: number;
  exams?: Exam[];
}
