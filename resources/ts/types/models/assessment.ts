import type { ClassSubject } from './classSubject';
import type { AssessmentAssignment } from './assessmentAssignment';
import { type User, type Question } from '@/types';

export type AssessmentType = 'homework' | 'exam' | 'practical' | 'quiz' | 'project';

export type DeliveryMode = 'supervised' | 'homework';

export interface Assessment {
    id: number;
    class_subject_id: number;
    title: string;
    description?: string;
    type: AssessmentType;
    delivery_mode: DeliveryMode;
    coefficient: number;
    duration_minutes: number | null;
    scheduled_at: string | null;
    due_date?: string;
    is_published: boolean;
    has_ended: boolean;
    shuffle_questions: boolean;
    show_results_immediately: boolean;
    show_correct_answers: boolean;
    allow_late_submission: boolean;
    results_available_at: string | null;
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
    delivery_mode: DeliveryMode;
    coefficient: number;
    duration: number;
    assessment_date: string;
    due_date?: string;
    is_published?: boolean;
    shuffle_questions?: boolean;
    show_results_immediately?: boolean;
    show_correct_answers?: boolean;
    allow_late_submission?: boolean;
    questions?: QuestionFormData[];
    deletedQuestionIds?: number[];
    deletedChoiceIds?: number[];
}

export interface QuestionFormData {
    id?: number;
    content: string;
    type: 'multiple' | 'text' | 'one_choice' | 'boolean' | 'file';
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

export interface AvailabilityStatus {
    available: boolean;
    reason: string | null;
}
