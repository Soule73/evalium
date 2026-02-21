import {
    type Assessment,
    type AssessmentAssignment,
    type Question,
    type Answer,
    type QuestionResult,
} from '@/types';
import { buildQuestionResult } from '@/utils/assessment/utils';
import { useFormatters } from '@/hooks/shared/useFormatters';
import { useMemo, useCallback } from 'react';

interface UseAssessmentResultParams {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    userAnswers: Record<number, Answer>;
    canShowCorrectAnswers: boolean;
}

/**
 * Custom React hook to compute and provide assessment result-related data and utilities.
 * The canShowCorrectAnswers flag is determined server-side to prevent cheating.
 */
const useAssessmentResults = ({
    assessment,
    assignment,
    userAnswers,
    canShowCorrectAnswers,
}: UseAssessmentResultParams) => {
    const { formatAssessmentAssignmentStatus } = useFormatters();
    const questions = useMemo(() => assessment?.questions ?? [], [assessment?.questions]);
    const assessmentIsActive = assessment.is_published;
    const assignmentScore = assignment.score;
    const assignmentAutoScore = assignment.auto_score;
    const assignmentStatus = assignment.status;
    const showResultsImmediately = assessment.show_results_immediately ?? true;

    const totalPoints = useMemo(
        () => questions.reduce((sum, q) => sum + (q.points || 0), 0),
        [questions],
    );

    const finalScore = useMemo(
        () => assignmentScore ?? assignmentAutoScore,
        [assignmentScore, assignmentAutoScore],
    );

    const isPendingReview = useMemo(() => assignmentStatus !== 'graded', [assignmentStatus]);

    const formattedAssignmentStatus = formatAssessmentAssignmentStatus(assignmentStatus);

    const showCorrectAnswers = canShowCorrectAnswers;

    const getQuestionResult = useCallback(
        (question: Question): QuestionResult =>
            buildQuestionResult(question, userAnswers[question.id]),
        [userAnswers],
    );

    return {
        totalPoints,
        finalScore,
        isPendingReview,
        assignmentStatus: formattedAssignmentStatus,
        showCorrectAnswers,
        showResultsImmediately,
        getQuestionResult,
        assessmentIsActive,
    };
};

export default useAssessmentResults;
