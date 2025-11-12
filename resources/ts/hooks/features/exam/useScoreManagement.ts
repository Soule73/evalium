import { useState, useMemo, useCallback } from 'react';
import { Question } from '@/types';
import { validateScore, calculatePercentage, formatScoresForSave, getCorrectionStatus } from '@/utils';

interface UseScoreManagementParams {
    questions: Question[];
    userAnswers: Record<number, any>;
    calculateQuestionScore: (question: Question) => number;
    totalPoints: number;
}

/**
 * Hook pour gérer les scores en mode correction
 */
const useScoreManagement = ({
    questions,
    userAnswers,
    calculateQuestionScore,
    totalPoints
}: UseScoreManagementParams) => {
    const initialScores = useMemo(() => {
        const scores: Record<number, number> = {};
        questions.forEach(question => {
            const existingScore = userAnswers[question.id]?.score;

            if (existingScore !== null && existingScore !== undefined) {
                scores[question.id] = existingScore;
            } else {
                scores[question.id] = calculateQuestionScore(question);
            }
        });
        return scores;
    }, [questions, userAnswers, calculateQuestionScore]);

    const [scores, setScores] = useState<Record<number, number>>(initialScores);

    const calculatedTotalScore = useMemo(() => {
        return Object.values(scores).reduce((sum, score) => sum + score, 0);
    }, [scores]);

    const percentage = useMemo(() => {
        return calculatePercentage(calculatedTotalScore, totalPoints);
    }, [calculatedTotalScore, totalPoints]);

    const correctionStatus = useMemo(() => {
        return getCorrectionStatus(calculatedTotalScore);
    }, [calculatedTotalScore]);

    const handleScoreChange = useCallback((questionId: number, newScore: number, maxScore: number) => {
        const validScore = validateScore(newScore, maxScore);
        setScores(prev => ({
            ...prev,
            [questionId]: validScore
        }));
    }, []);

    const getScoresForSave = useCallback(() => {
        return formatScoresForSave(scores);
    }, [scores]);

    return {
        scores,
        calculatedTotalScore,
        percentage,
        correctionStatus,
        handleScoreChange,
        getScoresForSave
    };
};

export default useScoreManagement;