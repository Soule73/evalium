import { Exam, QuestionFormData } from '../index';
import { PaginatedResponse, ApiResponse } from './common';

export interface GetExamsResponse extends PaginatedResponse<Exam> { }

export interface GetExamResponse extends ApiResponse<Exam> { }

export interface CreateExamRequest {
    title: string;
    description?: string;
    duration: number;
    is_active: boolean;
    start_time?: string;
    end_time?: string;
    questions: QuestionFormData[];
}

export interface UpdateExamRequest extends Partial<CreateExamRequest> {
    deletedQuestionIds?: number[];
    deletedChoiceIds?: number[];
}

export interface AssignExamToGroupsRequest {
    group_ids: number[];
    assigned_at?: string;
}

export interface DuplicateExamResponse extends ApiResponse<Exam> { }

export interface ExamStatsResponse {
    total_assigned: number;
    not_started: number;
    in_progress: number;
    completed: number;
    average_score: number | null;
    pass_rate: number | null;
}
