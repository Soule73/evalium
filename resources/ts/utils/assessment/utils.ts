import {
    type Question,
    type Answer,
    type AssessmentAssignment,
    type Choice,
    type QuestionResult,
} from '@/types';
import { formatGrade } from '@/utils/formatting/formatters';

interface DeliveryModeDefaults {
    shuffle_questions: boolean;
    duration?: number;
}

/**
 * Computes side-effect field defaults when delivery_mode changes.
 */
export const getDeliveryModeDefaults = (deliveryMode: string): DeliveryModeDefaults => {
    const isSupervisedMode = deliveryMode === 'supervised';
    return {
        shuffle_questions: isSupervisedMode,
        ...(isSupervisedMode ? {} : { duration: 0 }),
    };
};

/**
 * Calculates the total possible points from a list of questions.
 */
export const calculateTotalPoints = (questions: Question[]): number => {
    return questions.reduce((sum, q) => sum + (q.points || 0), 0);
};

/**
 * Calculates the total score from a record of user answers.
 */
export const calculateTotalScore = (userAnswers: Record<number, Answer>): number => {
    return Object.values(userAnswers || {}).reduce((sum, answer) => sum + (answer.score || 0), 0);
};

/**
 * Builds a map of question_id -> score from user answers.
 */
export const buildScoresMap = (userAnswers: Record<number, Answer>): Record<number, number> => {
    const result: Record<number, number> = {};
    Object.values(userAnswers || {}).forEach((answer) => {
        if (answer.question_id) {
            result[answer.question_id] = answer.score || 0;
        }
    });
    return result;
};

/**
 * Builds a QuestionResult from a question and its corresponding answer.
 * Computes isCorrect for choice-based questions and infers score from correctness
 * when no explicit score is stored. Supports optional overrides for editable scores/feedbacks.
 */
export const buildQuestionResult = (
    question: Question,
    answer: Answer | undefined,
    overrides?: { scores?: Record<number, number>; feedbacks?: Record<number, string> },
): QuestionResult => {
    if (!answer) {
        return {
            isCorrect: null,
            userChoices: [],
            hasMultipleAnswers: question.type === 'multiple',
            feedback: overrides?.feedbacks?.[question.id] ?? null,
            score: overrides?.scores?.[question.id] ?? (question.type === 'text' ? undefined : 0),
        };
    }

    if (question.type === 'file') {
        const explicitScore = overrides?.scores?.[question.id];
        return {
            isCorrect: null,
            userChoices: [],
            hasMultipleAnswers: false,
            fileAnswer: answer.file_path ? answer : undefined,
            feedback: overrides?.feedbacks?.[question.id] ?? answer.feedback ?? null,
            score: explicitScore !== undefined ? explicitScore : answer.score,
        };
    }

    const isMultipleChoice = question.type === 'multiple';
    const userChoices: Choice[] = [];
    let isCorrect: boolean | null = null;

    if (isMultipleChoice && answer.choices) {
        answer.choices.forEach((c) => {
            if (c.choice) {
                userChoices.push(c.choice);
            }
        });
        const correctChoices = (question.choices ?? []).filter((c) => c.is_correct);
        const selectedIds = new Set(userChoices.map((c) => c.id));
        const correctIds = new Set(correctChoices.map((c) => c.id));
        isCorrect =
            correctIds.size === selectedIds.size &&
            [...correctIds].every((id) => selectedIds.has(id));
    } else if (answer.choice) {
        userChoices.push(answer.choice);
        if (question.type === 'one_choice' || question.type === 'boolean') {
            isCorrect = answer.choice.is_correct ?? null;
        }
    }

    const effectiveScore =
        overrides?.scores?.[question.id] !== undefined
            ? overrides.scores[question.id]
            : answer.score !== undefined && answer.score !== null
              ? answer.score
              : isCorrect === true
                ? (question.points ?? 0)
                : question.type === 'text'
                  ? undefined
                  : 0;

    return {
        isCorrect,
        userChoices,
        hasMultipleAnswers: isMultipleChoice,
        userText: answer.answer_text,
        feedback: overrides?.feedbacks?.[question.id] ?? answer.feedback ?? null,
        score: effectiveScore,
    };
};
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
export const formatScoresForSave = (
    scores: Record<number, number>,
): Array<{ question_id: number; score: number }> => {
    return Object.entries(scores).map(([questionId, score]) => ({
        question_id: parseInt(questionId),
        score: score,
    }));
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
export const hasUserResponse = (result: {
    userChoices?: unknown[];
    userText?: string;
    fileAnswer?: { file_path?: string };
}): boolean => {
    if (result.fileAnswer?.file_path) return true;
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
export const calculateScoreDisplay = (
    assignment: AssessmentAssignment,
): { text: string; colorClass: string } | null => {
    if (assignment.status !== 'graded') {
        return null;
    }

    const finalScore = assignment.score ?? assignment.auto_score;

    const totalPoints =
        assignment.assessment?.questions?.reduce(
            (sum: number, q: { points: number }) => sum + (q.points || 0),
            0,
        ) || 20;

    if (finalScore !== null && finalScore !== undefined && totalPoints > 0) {
        return formatGrade(Math.min(finalScore, totalPoints), totalPoints);
    }

    return null;
};

/**
 * Determines if assessment results can be shown based on assignment status and grading policy.
 */
export const canShowAssessmentResults = (
    assignmentStatus: string,
    releaseResultsAfterGrading: boolean = false,
): boolean => {
    if (
        !releaseResultsAfterGrading &&
        (assignmentStatus === 'submitted' || assignmentStatus === 'graded')
    ) {
        return true;
    }
    return assignmentStatus === 'graded';
};

/**
 * Returns the list of possible assignment statuses.
 */
export const getAssignmentStatus = (): string[] => {
    return ['submitted', 'graded'];
};
