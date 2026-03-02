import { useMemo } from 'react';
import { type AssessmentAssignment } from '@evalium/utils/types';
import { getFinalScore, calculatePercentage } from '@evalium/utils/assessment/utils';

interface UseAssessmentScoringParams {
    assignment: AssessmentAssignment;
    totalPoints: number;
}

/**
 * Hook for final score and percentage calculations in student assessment results.
 */
const useAssessmentScoring = ({ assignment, totalPoints }: UseAssessmentScoringParams) => {
    const finalScore = useMemo(() => getFinalScore(assignment), [assignment]);

    const finalPercentage = useMemo(
        () => calculatePercentage(finalScore, totalPoints),
        [finalScore, totalPoints],
    );

    return { finalScore, finalPercentage };
};

export default useAssessmentScoring;
