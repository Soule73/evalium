import type { Choice } from './choice';

export interface Answer {
    id: number;
    assignment_id: number;
    question_id: number;
    choice_id?: number;
    answer_text?: string;
    score?: number;
    feedback?: string;
    created_at: string;
    updated_at: string;

    choice?: Choice;
    choices?: Array<{
        choice_id: number;
        choice: Choice;
    }>;
    selectedChoice?: Choice;
}
