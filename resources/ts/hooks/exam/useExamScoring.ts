import { useMemo } from 'react';
import { Question, Exam, ExamAssignment } from '@/types';
import useExamResults from './useExamResults';

interface UseExamScoringParams {
    exam: Exam;
    assignment: ExamAssignment;
    userAnswers: Record<number, any>;
}

/**
 * Hook commun pour les calculs de score et pourcentages dans les examens
 */
const useExamScoring = ({ exam, assignment, userAnswers }: UseExamScoringParams) => {
    const { totalPoints, finalScore, getQuestionResult } = useExamResults({ exam, assignment, userAnswers });

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
            exam.questions?.forEach(question => {
                const existingScore = userAnswers[question.id]?.score;

                if (existingScore !== null && existingScore !== undefined) {
                    initialScores[question.id] = existingScore;
                } else {
                    initialScores[question.id] = calculateQuestionScore(question);
                }
            });
            return initialScores;
        };
    }, [exam.questions, userAnswers, calculateQuestionScore]);

    return {
        totalPoints,
        finalScore,
        finalPercentage,
        calculateQuestionScore,
        calculatePercentage,
        calculateTotalScore,
        initializeScores,
        getQuestionResult
    };
};

export default useExamScoring;