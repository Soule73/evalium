/**
 * Formats assessment answers for backend submission
 *
 * @param answers - Raw answers object
 * @returns Formatted answers ready for backend
 *
 * @example
 * formatAnswersForSubmission({ 1: [2, 3], 2: "text answer", 3: 5 })
 * // { 1: [2, 3], 2: "text answer", 3: 5 }
 */
export function formatAnswersForSubmission(
    answers: Record<number, string | number | number[]>,
): Record<number, string | number | number[]> {
    const formattedAnswers: Record<number, string | number | number[]> = {};

    Object.entries(answers).forEach(([questionId, value]) => {
        const qId = parseInt(questionId);

        if (Array.isArray(value)) {
            const choiceIds = value.filter((id) => typeof id === 'number');
            formattedAnswers[qId] = choiceIds;
        } else {
            formattedAnswers[qId] = value;
        }
    });

    return formattedAnswers;
}

/**
 * Checks if an answer is valid (not empty)
 *
 * @param value - Answer value
 * @returns True if answer is valid
 *
 * @example
 * isAnswerValid([1, 2]) // true
 * isAnswerValid([]) // false
 * isAnswerValid("text") // true
 * isAnswerValid("") // false
 */
export function isAnswerValid(value: string | number | number[]): boolean {
    if (Array.isArray(value)) {
        return value.length > 0;
    }

    if (typeof value === 'string') {
        return value.trim().length > 0;
    }

    return value !== null && value !== undefined;
}

/**
 * Counts answered questions
 *
 * @param answers - Answers object
 * @returns Number of answered questions
 *
 * @example
 * countAnsweredQuestions({ 1: [2], 2: "", 3: 5 }) // 2
 */
export function countAnsweredQuestions(
    answers: Record<number, string | number | number[]>,
): number {
    return Object.values(answers).filter(isAnswerValid).length;
}

/**
 * Calculates assessment completion percentage
 *
 * @param answers - Current answers
 * @param totalQuestions - Total number of questions
 * @returns Completion percentage (0-100)
 *
 * @example
 * getAssessmentCompletionPercentage({ 1: [2], 3: 5 }, 10) // 20
 */
export function getAssessmentCompletionPercentage(
    answers: Record<number, string | number | number[]>,
    totalQuestions: number,
): number {
    if (totalQuestions === 0) return 0;

    const answeredCount = countAnsweredQuestions(answers);
    return Math.round((answeredCount / totalQuestions) * 100);
}

/**
 * Checks if all questions are answered
 *
 * @param answers - Current answers
 * @param totalQuestions - Total number of questions
 * @returns True if all questions are answered
 *
 * @example
 * areAllQuestionsAnswered({ 1: [2], 2: "text", 3: 5 }, 3) // true
 * areAllQuestionsAnswered({ 1: [2], 2: "" }, 3) // false
 */
export function areAllQuestionsAnswered(
    answers: Record<number, string | number | number[]>,
    totalQuestions: number,
): boolean {
    return countAnsweredQuestions(answers) === totalQuestions;
}

/**
 * Gets unanswered question IDs
 *
 * @param answers - Current answers
 * @param questionIds - All question IDs
 * @returns Array of unanswered question IDs
 *
 * @example
 * getUnansweredQuestions({ 1: [2], 3: 5 }, [1, 2, 3, 4]) // [2, 4]
 */
export function getUnansweredQuestions(
    answers: Record<number, string | number | number[]>,
    questionIds: number[],
): number[] {
    return questionIds.filter((id) => !isAnswerValid(answers[id]));
}
