import { type Question, type AssessmentAssignment } from '@/types';
import { trans } from '../helpers/translations';


/**
 * Formats the assessment score as a string, optionally indicating if the score is pending review
 * and displaying an automatic score if available.
 *
 * @param score - The obtained score, or `undefined` if not yet graded.
 * @param totalPoints - The total possible points for the assessment.
 * @param isPendingReview - Optional flag indicating if the assessment is pending manual review.
 * @param autoScore - Optional automatic score (e.g., for multiple-choice questions).
 * @returns A formatted string representing the score, including special handling for pending reviews.
 */
export const formatAssessmentScore = (
    score: number | undefined,
    totalPoints: number,
    isPendingReview?: boolean,
    autoScore?: number
): string => {
    if (isPendingReview && autoScore !== undefined) {
        return trans('formatters.partial_score_mcq', { score: autoScore, total: totalPoints });
    }
    return trans('formatters.score_format', { score: score || 0, total: totalPoints });
};


/**
 * Calculates the percentage score based on the given score and total possible points.
 *
 * @param score - The achieved score.
 * @param totalPoints - The total possible points.
 * @returns The percentage score as a whole number (rounded). Returns 0 if totalPoints is 0 or less.
 */
export const calculatePercentage = (score: number, totalPoints: number): number => {
    return totalPoints > 0 ? Math.round((score / totalPoints) * 100) : 0;
};


/**
 * Ensures that a given score is within the valid range of 0 to maxScore (inclusive).
 *
 * @param score - The score to validate.
 * @param maxScore - The maximum allowed score.
 * @returns The validated score, clamped between 0 and maxScore.
 */
export const validateScore = (score: number, maxScore: number): number => {
    return Math.max(0, Math.min(score, maxScore));
};


/**
 * Determines whether a given question requires manual grading.
 *
 * @param question - The question object to evaluate.
 * @returns `true` if the question type is 'text', indicating manual grading is needed; otherwise, `false`.
 */
export const requiresManualGrading = (question: Question): boolean => {
    return question.type === 'text';
};


/**
 * Converts a record of question scores into an array of objects suitable for saving.
 *
 * @param scores - An object where the keys are question IDs (as numbers) and the values are the corresponding scores.
 * @returns An array of objects, each containing a `question_id` and its associated `score`.
 */
export const formatScoresForSave = (scores: Record<number, number>): Array<{ question_id: number, score: number }> => {
    return Object.entries(scores).map(([questionId, score]) => ({
        question_id: parseInt(questionId),
        score: score
    }));
};


/**
 * Returns the correction status based on the calculated score.
 *
 * @param calculatedScore - The score that has been calculated for the assessment.
 * @returns A string indicating the correction status:
 *          - 'En cours de correction' if the score is greater than 0.
 *          - 'Non noté' if the score is 0 or less.
 */
export const getCorrectionStatus = (calculatedScore: number): string => {
    return calculatedScore > 0
        ? trans('formatters.correction_in_progress')
        : trans('formatters.not_graded');
};

/**
 * Determines whether the given result object contains a user response.
 *
 * Checks if the `result` object has either a non-empty `userChoices` array
 * or a non-empty, trimmed `userText` string.
 *
 * @param result - The object to check for user responses. Expected to have `userChoices` (array) and/or `userText` (string) properties.
 * @returns `true` if the user has provided a response; otherwise, `false`.
 */
export const hasUserResponse = (result: { userChoices?: unknown[]; userText?: string }): boolean => {
    if (result.userChoices && result.userChoices.length > 0) {
        return true;
    }

    if (result.userText && result.userText.trim() !== '') {
        return true;
    }

    return false;
};

/**
 * Calculates and formats the display of a score for a given assessment assignment.
 *
 * This function determines the final score from either `assignment.score` or `assignment.auto_score`,
 * calculates the total possible points from the assessment's questions (defaulting to 20 if not available),
 * and computes the percentage score. It then assigns a color class based on the percentage:
 * - Green for 90% and above
 * - Blue for 70% to 89%
 * - Yellow for 50% to 69%
 * - Red for below 50%
 *
 * @param assignment - The assessment assignment object containing score and assessment details.
 * @returns An object with the formatted score text and a color class, or `null` if the score is not available or total points is zero.
 */
export const calculateScoreDisplay = (assignment: AssessmentAssignment): { text: string; colorClass: string } | null => {
    if (assignment.status !== 'graded') {
        return null;
    }

    const finalScore = assignment.score ?? assignment.auto_score;

    const totalPoints = assignment.assessment?.questions?.reduce((sum: number, q: { points: number }) => sum + (q.points || 0), 0) || 20;

    if (finalScore !== null && finalScore !== undefined && totalPoints > 0) {

        const limitedScore = Math.min(finalScore, totalPoints);

        const percentage = Math.round((limitedScore / totalPoints) * 100);

        let colorClass: string;
        if (percentage >= 90) colorClass = 'text-green-600';
        else if (percentage >= 70) colorClass = 'text-blue-600';
        else if (percentage >= 50) colorClass = 'text-yellow-600';
        else colorClass = 'text-red-600';

        return {
            text: `${limitedScore}/${totalPoints} (${percentage}%)`,
            colorClass
        };
    }

    return null;
};