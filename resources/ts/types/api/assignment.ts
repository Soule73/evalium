import { ExamAssignment, Answer } from '../index';
import { PaginatedResponse, ApiResponse } from './common';

export interface GetAssignmentsResponse extends PaginatedResponse<ExamAssignment> { }

export interface GetAssignmentResponse extends ApiResponse<ExamAssignment> { }

export interface CreateAssignmentRequest {
    student_id: number;
    exam_id: number;
    assigned_at?: string;
}

export interface StartExamRequest {
    exam_id: number;
}

export interface SaveAnswerRequest {
    question_id: number;
    choice_id?: number;
    choice_ids?: number[];
    answer_text?: string;
}

export interface SubmitExamRequest {
    assignment_id: number;
    forced?: boolean;
    security_violation?: string;
}

export interface GradeAnswerRequest {
    question_id: number;
    score: number;
    feedback?: string;
}

export interface BulkGradeRequest {
    scores: Array<{
        question_id: number;
        score: number;
        feedback?: string;
    }>;
}

export interface AssignmentWithAnswers extends ExamAssignment {
    answers: Answer[];
}
