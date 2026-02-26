import type { Choice } from './choice';

export interface Answer {
    id: number;
    assessment_assignment_id: number;
    question_id: number;
    choice_id?: number;
    answer_text?: string;
    file_name?: string;
    file_path?: string;
    file_size?: number;
    mime_type?: string;
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
