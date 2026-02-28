import { type Assessment, type AssessmentAssignment } from '@/types';
import { useMemo } from 'react';
import { calculateTotalPoints, formatAssignmentStatus } from '@/utils/assessment';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface UseAssessmentResultParams {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    canShowCorrectAnswers: boolean;
}

/**
 * Custom React hook to compute and provide assessment result-related data and utilities.
 * The canShowCorrectAnswers flag is determined server-side to prevent cheating.
 */
const useAssessmentResults = ({
    assessment,
    assignment,
    canShowCorrectAnswers,
}: UseAssessmentResultParams) => {
    const { t } = useTranslations();
    const questions = useMemo(() => assessment?.questions ?? [], [assessment?.questions]);
    const assessmentIsActive = assessment.is_published;
    const assignmentStatus = assignment.status;
    const releaseResultsAfterGrading = assessment.release_results_after_grading ?? false;

    const totalPoints = useMemo(() => calculateTotalPoints(questions), [questions]);

    const isPendingReview = useMemo(() => assignmentStatus !== 'graded', [assignmentStatus]);

    const formattedAssignmentStatus = useMemo(
        () => formatAssignmentStatus(t, assignmentStatus),
        [t, assignmentStatus],
    );

    const showCorrectAnswers = canShowCorrectAnswers;

    return {
        totalPoints,
        isPendingReview,
        assignmentStatus: formattedAssignmentStatus,
        showCorrectAnswers,
        releaseResultsAfterGrading,
        assessmentIsActive,
    };
};

export default useAssessmentResults;
