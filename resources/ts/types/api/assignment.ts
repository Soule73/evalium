import { AssessmentAssignment, Answer } from '../index';
import { PaginatedResponse, ApiResponse } from './common';

export interface GetAssignmentsResponse extends PaginatedResponse<AssessmentAssignment> { }

export interface GetAssignmentResponse extends ApiResponse<AssessmentAssignment> { }

export interface CreateAssignmentRequest {
    student_id: number;
    assessment_id: number;
}

export interface SaveAnswerRequest {
    question_id: number;
    choice_id?: number;
    choice_ids?: number[];
    answer_text?: string;
}

export interface SubmitAssessmentRequest {
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

export interface AssignmentWithAnswers extends AssessmentAssignment {
    answers: Answer[];
}
