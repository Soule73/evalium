import { useMemo } from 'react';
import { type AssessmentAssignment } from '@/types';

interface UseAssessmentScoringParams {
    assignment: AssessmentAssignment;
    totalPoints: number;
}

/**
 * Hook for final score and percentage calculations in student assessment results.
 */
const useAssessmentScoring = ({ assignment, totalPoints }: UseAssessmentScoringParams) => {
    const finalScore = useMemo(
        () => assignment.score ?? assignment.auto_score ?? 0,
        [assignment.score, assignment.auto_score],
    );

    const finalPercentage = useMemo(() => {
        return totalPoints > 0 ? Math.round(((finalScore || 0) / totalPoints) * 100) : 0;
    }, [finalScore, totalPoints]);

    return { finalScore, finalPercentage };
};

export default useAssessmentScoring;
