import { User, Answer } from './shared';
import type { Assessment } from './assessment';

export type AssessmentAssignmentStatus = 'not_submitted' | 'submitted' | 'graded';

export interface AssessmentAssignment {
  id: number;
  assessment_id: number;
  student_id: number;
  submitted_at?: string;
  graded_at?: string;
  score?: number;
  auto_score?: number;
  status: AssessmentAssignmentStatus;
  teacher_notes?: string;
  security_violation?: string;
  forced_submission: boolean;
  created_at: string;
  updated_at: string;

  assessment?: Assessment;
  student?: User;
  answers?: Answer[];

  time_spent?: number;
  progress_percentage?: number;
}

export interface SaveAnswersData {
  answers: {
    question_id: number;
    choice_id?: number;
    choice_ids?: number[];
    answer_text?: string;
  }[];
}

export interface GradingData {
  scores: {
    question_id: number;
    score: number;
    feedback?: string;
  }[];
}
