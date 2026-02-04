import type { ClassSubject } from './classSubject';
import type { AssessmentAssignment } from './assessmentAssignment';
import { User, Question } from '@/types';

export type AssessmentType = 'devoir' | 'examen' | 'tp' | 'controle' | 'projet';

export interface Assessment {
  id: number;
  class_subject_id: number;
  title: string;
  description?: string;
  type: AssessmentType;
  coefficient: number;
  duration_minutes: number;
  scheduled_at: string;
  is_published: boolean;
  created_at: string;
  updated_at: string;

  class_subject?: ClassSubject;
  teacher?: User;
  questions?: Question[];
  assignments?: AssessmentAssignment[];

  questions_count?: number;
  total_points?: number;
  assignments_count?: number;
  completed_assignments_count?: number;
}

export interface AssessmentFormData {
  class_subject_id: number;
  title: string;
  description?: string;
  type: AssessmentType;
  coefficient: number;
  duration: number;
  assessment_date: string;
  is_published?: boolean;
  questions?: QuestionFormData[];
  deletedQuestionIds?: number[];
  deletedChoiceIds?: number[];
}

export interface QuestionFormData {
  id?: number;
  content: string;
  type: 'multiple' | 'text' | 'one_choice' | 'boolean';
  points: number;
  order_index: number;
  choices: ChoiceFormData[];
}

export interface ChoiceFormData {
  id?: number;
  content: string;
  is_correct: boolean;
  order_index: number;
}

export interface AssessmentStatistics {
  total_assigned: number;
  in_progress: number;
  not_started: number;
  completed: number;
  average_score?: number;
  highest_score?: number;
  lowest_score?: number;
}
