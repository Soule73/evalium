import { useMemo, useCallback } from 'react';
import type { Assessment, Answer, Question, QuestionResult } from '@/types';
import {
    calculateTotalPoints,
    calculatePercentage,
    buildScoresMap,
    buildQuestionResult,
} from '@/utils/assessment/utils';

interface UseAssessmentReviewParams {
    assessment: Assessment;
    userAnswers: Record<number, Answer>;
    scoreOverrides?: Record<number, number>;
    feedbackOverrides?: Record<number, string>;
}

interface UseAssessmentReviewReturn {
    totalPoints: number;
    calculatedTotalScore: number;
    percentage: number;
    scores: Record<number, number>;
    getQuestionResult: (question: Question) => QuestionResult;
}

/**
 * Shared hook for assessment review/grading computations.
 *
 * Centralizes totalPoints, totalScore, percentage, scores map,
 * and getQuestionResult logic used by Review, Grade, and Admin assignment pages.
 * Accepts optional score/feedback overrides for editable grading mode.
 */
const useAssessmentReview = ({
    assessment,
    userAnswers,
    scoreOverrides,
    feedbackOverrides,
}: UseAssessmentReviewParams): UseAssessmentReviewReturn => {
    const questions = useMemo(() => assessment.questions ?? [], [assessment.questions]);

    const totalPoints = useMemo(() => calculateTotalPoints(questions), [questions]);

    const scores = useMemo(() => {
        if (scoreOverrides) {
            return scoreOverrides;
        }
        return buildScoresMap(userAnswers);
    }, [userAnswers, scoreOverrides]);

    const calculatedTotalScore = useMemo(
        () => Object.values(scores).reduce((sum, score) => sum + score, 0),
        [scores],
    );

    const percentage = useMemo(
        () => calculatePercentage(calculatedTotalScore, totalPoints),
        [calculatedTotalScore, totalPoints],
    );

    const overrides = useMemo(() => {
        if (!scoreOverrides && !feedbackOverrides) return undefined;
        return { scores: scoreOverrides, feedbacks: feedbackOverrides };
    }, [scoreOverrides, feedbackOverrides]);

    const getQuestionResult = useCallback(
        (question: Question): QuestionResult =>
            buildQuestionResult(question, userAnswers[question.id], overrides),
        [userAnswers, overrides],
    );

    return { totalPoints, calculatedTotalScore, percentage, scores, getQuestionResult };
};

export default useAssessmentReview;
