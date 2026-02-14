import { useMemo } from 'react';
import { type Question, type Assessment, type AssessmentAssignment, type Answer, type QuestionResult } from '@/types';

interface UseAssessmentScoringParams {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    userAnswers: Record<number, Answer>;
    totalPoints: number;
    getQuestionResult: (question: Question) => QuestionResult;
}

/**
 * Hook for score and percentage calculations in assessments
 */
const useAssessmentScoring = ({ assessment, assignment, userAnswers, totalPoints, getQuestionResult }: UseAssessmentScoringParams) => {
    const finalScore = useMemo(
        () => assignment.score ?? assignment.auto_score ?? 0,
        [assignment.score, assignment.auto_score]
    );

    const calculateQuestionScore = useMemo(() => {
        return (question: Question): number => {
            const result = getQuestionResult(question);

            if (question.type === 'text') {
                return userAnswers[question.id]?.score ?? 0;
            }

            if (result?.score !== undefined && result?.score !== null) {
                return result.score;
            }

            if (result?.isCorrect) {
                return question.points || 0;
            }

            return 0;
        };
    }, [getQuestionResult, userAnswers]);

    const calculatePercentage = useMemo(() => {
        return (score: number): number => {
            return totalPoints > 0 ? Math.round((score / totalPoints) * 100) : 0;
        };
    }, [totalPoints]);

    const finalPercentage = useMemo(() => {
        return calculatePercentage(finalScore || 0);
    }, [calculatePercentage, finalScore]);

    const calculateTotalScore = useMemo(() => {
        return (scores: Record<number, number>): number => {
            return Object.values(scores).reduce((sum, score) => sum + score, 0);
        };
    }, []);

    const initializeScores = useMemo(() => {
        return (): Record<number, number> => {
            const initialScores: Record<number, number> = {};
            assessment.questions?.forEach(question => {
                const existingScore = userAnswers[question.id]?.score;

                if (existingScore !== null && existingScore !== undefined) {
                    initialScores[question.id] = existingScore;
                } else {
                    initialScores[question.id] = calculateQuestionScore(question);
                }
            });
            return initialScores;
        };
    }, [assessment.questions, userAnswers, calculateQuestionScore]);

    return {
        totalPoints,
        finalScore,
        finalPercentage,
        calculateQuestionScore,
        calculatePercentage,
        calculateTotalScore,
        initializeScores,
    };
};

export default useAssessmentScoring;