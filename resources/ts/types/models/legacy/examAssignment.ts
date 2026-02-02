import type { Exam } from './exam';
import type { User } from '../shared/user';
import type { Answer } from '../shared/answer';

export type AssignmentStatus = 'submitted' | 'graded';

export interface ExamAssignment {
  id: number;
  student_id: number;
  exam_id: number;
  assigned_at: string;
  started_at?: string;
  submitted_at?: string;
  score?: number;
  auto_score?: number;
  status: AssignmentStatus;
  teacher_notes?: string;
  security_violation?: string;
  forced_submission: boolean;
  created_at: string;
  updated_at: string;

  exam?: Exam;
  student?: User;
  answers?: Answer[];
}
