import type { Choice } from './choice';

export type QuestionType = 'multiple' | 'text' | 'one_choice' | 'boolean';

export interface Question {
  id: number;
  assessment_id?: number;
  type: QuestionType;
  content: string;
  points: number;
  order_index: number;
  created_at: string;
  updated_at: string;
  choices?: Choice[];
  correct_answer?: string;
}
