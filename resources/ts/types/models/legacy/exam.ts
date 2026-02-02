import type { User } from '../shared/user';
import type { Question } from '../shared/question';
import type { ExamAssignment } from './examAssignment';
import type { Group } from './group';

export interface Exam {
  id: number;
  title: string;
  description?: string;
  duration: number;
  is_active: boolean;
  start_time?: string;
  end_time?: string;
  teacher_id: number;
  created_at: string;
  updated_at: string;

  creator?: User;
  questions?: Question[];
  assignments?: ExamAssignment[];
  groups?: Group[];

  questions_count?: number;
  total_points?: number;
}
