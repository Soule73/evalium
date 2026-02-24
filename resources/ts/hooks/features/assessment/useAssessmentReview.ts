import { useMemo } from 'react';
import type { Assessment, Answer } from '@/types';
import {
    calculateTotalPoints,
    calculatePercentage,
    buildScoresMap,
} from '@/utils/assessment/utils';

interface UseAssessmentReviewParams {
    assessment: Assessment;
    userAnswers: Record<number, Answer>;
    scoreOverrides?: Record<number, number>;
}

interface UseAssessmentReviewReturn {
    totalPoints: number;
    calculatedTotalScore: number;
    percentage: number;
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

    return { totalPoints, calculatedTotalScore, percentage };
};

export default useAssessmentReview;
