import { useState, useMemo } from 'react';
import { Question } from '@/types';
import { validateScore, calculatePercentage, formatScoresForSave, getCorrectionStatus } from '@/utils/examUtils';

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
    // État pour les scores modifiés
    const [scores, setScores] = useState<Record<number, number>>(() => {
        const initialScores: Record<number, number> = {};
        questions.forEach(question => {
            const existingScore = userAnswers[question.id]?.score;

            if (existingScore !== null && existingScore !== undefined) {
                // Utiliser le score existant s'il y en a un
                initialScores[question.id] = existingScore;
            } else {
                // Calculer automatiquement pour les QCM
                initialScores[question.id] = calculateQuestionScore(question);
            }
        });
        return initialScores;
    });

    // Calculer le score total
    const calculatedTotalScore = useMemo(() => {
        return Object.values(scores).reduce((sum, score) => sum + score, 0);
    }, [scores]);

    // Calculer le pourcentage
    const percentage = useMemo(() => {
        return calculatePercentage(calculatedTotalScore, totalPoints);
    }, [calculatedTotalScore, totalPoints]);

    // Statut de correction
    const correctionStatus = useMemo(() => {
        return getCorrectionStatus(calculatedTotalScore);
    }, [calculatedTotalScore]);

    // Fonction pour changer un score
    const handleScoreChange = (questionId: number, newScore: number, maxScore: number) => {
        const validScore = validateScore(newScore, maxScore);
        setScores(prev => ({
            ...prev,
            [questionId]: validScore
        }));
    };

    // Préparer les données pour la sauvegarde
    const getScoresForSave = () => {
        return formatScoresForSave(scores);
    };

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