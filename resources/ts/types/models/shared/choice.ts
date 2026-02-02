export interface Choice {
  id: number;
  question_id: number;
  content: string;
  is_correct: boolean;
  order_index: number;
  created_at: string;
  updated_at: string;
}

export interface QuestionResult {
  isCorrect: boolean | null;
  userChoices: Choice[];
  hasMultipleAnswers: boolean;
  userText?: string;
  feedback: string | null;
  score?: number;
}
