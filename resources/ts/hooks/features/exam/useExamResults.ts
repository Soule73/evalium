import { Exam, ExamAssignment, Question } from "@/types";
import { canShowExamResults, formatExamAssignmentStatus } from "@/utils";
import { useMemo } from "react";

interface UseExamResultParams {
    exam: Exam;
    assignment: ExamAssignment;
    userAnswers: Record<number, any>;
}


/**
 * Custom React hook to compute and provide exam result-related data and utilities.
 *
 * @param params - The parameters for the hook.
 * @param params.exam - The exam object containing questions and metadata.
 * @param params.assignment - The assignment object containing user-specific exam data such as score and status.
 * @param params.userAnswers - An object mapping question IDs to the user's answers.
 *
 * @returns An object containing:
 * - `totalPoints`: The total possible points for the exam.
 * - `finalScore`: The user's final score for the assignment.
 * - `isPendingReview`: Whether the assignment is pending review.
 * - `getQuestionResult`: A function to get the result for a specific question, including correctness and user choices.
 * - `assignmentStatus`: A formatted string representing the assignment's status.
 * - `examIsActive`: Whether the exam is currently active.
 *
 * @remarks
 * This hook uses `useMemo` to optimize calculations and avoid unnecessary recomputations.
 * The `getQuestionResult` function handles different question types (multiple choice, text, single choice)
 * and returns detailed information about the user's answer and its correctness.
 */
const useExamResults = ({ exam, assignment, userAnswers }: UseExamResultParams) => {
    const totalPoints = useMemo(
        () => exam?.questions?.reduce((sum, q) => sum + q.points, 0) ?? 0,
        [exam]
    );

    const finalScore = useMemo(
        () => assignment.score ?? assignment.auto_score,
        [assignment.score, assignment.auto_score]
    );

    const isPendingReview = useMemo(
        () => assignment.status !== "graded",
        [assignment.status]
    );

    const assignmentStatus = useMemo(
        () => formatExamAssignmentStatus(assignment.status),
        [assignment.status]
    );

    const showCorrectAnswers = useMemo(
        () => canShowExamResults(assignment.status),
        [assignment.status]
    );

    const examIsActive = useMemo(
        () => exam.is_active,
        [exam.is_active]
    );

    const getQuestionResult = useMemo(() => {
        return (question: Question) => {
            const userAnswer = userAnswers[question.id];

            if (!userAnswer) {
                return {
                    isCorrect: null,
                    userChoices: [],
                    hasMultipleAnswers: false,
                    feedback: null,
                };
            }

            if (question.type === "multiple") {
                if (userAnswer.type === 'multiple' && userAnswer.choices) {
                    const selectedChoices = userAnswer.choices.map((c: any) => c.choice);
                    const correctChoices = (question.choices ?? []).filter(c => c.is_correct);

                    const selectedChoiceIds = new Set(selectedChoices.map((choice: any) => choice.id));
                    const correctChoiceIds = new Set(correctChoices.map(choice => choice.id));

                    const hasAllCorrectChoices = correctChoiceIds.size === selectedChoiceIds.size &&
                        [...correctChoiceIds].every(id => selectedChoiceIds.has(id));

                    const score = userAnswer.score !== undefined && userAnswer.score !== null
                        ? userAnswer.score
                        : (hasAllCorrectChoices ? question.points : 0);

                    return {
                        isCorrect: hasAllCorrectChoices,
                        userChoices: selectedChoices,
                        hasMultipleAnswers: true,
                        feedback: userAnswer.feedback,
                        score: score,
                    };
                }

                return {
                    isCorrect: null,
                    userChoices: [],
                    hasMultipleAnswers: true,
                    feedback: userAnswer.feedback,
                    score: userAnswer.score ?? 0,
                };
            }

            if (question.type === "text") {
                return {
                    isCorrect: null,
                    userChoices: [],
                    userText: userAnswer.answer_text,
                    hasMultipleAnswers: false,
                    score: userAnswer.score,
                    feedback: userAnswer.feedback,
                };
            }

            if (userAnswer.type === 'single' && userAnswer.choice) {
                const isCorrect = userAnswer.choice.is_correct;
                const score = userAnswer.score !== undefined && userAnswer.score !== null
                    ? userAnswer.score
                    : (isCorrect ? question.points : 0);

                return {
                    isCorrect: isCorrect,
                    userChoices: [userAnswer.choice],
                    hasMultipleAnswers: false,
                    feedback: userAnswer.feedback,
                    score: score,
                };
            }

            const userChoice = userAnswer.choice || userAnswer.selectedChoice;
            const isCorrect = userChoice?.is_correct ?? null;
            const score = userAnswer.score !== undefined && userAnswer.score !== null
                ? userAnswer.score
                : (isCorrect ? question.points : 0);

            return {
                isCorrect: isCorrect,
                userChoices: userChoice ? [userChoice] : [],
                hasMultipleAnswers: false,
                feedback: userAnswer.feedback,
                score: score,
            };
        };
    }, [userAnswers]);

    return {
        totalPoints,
        finalScore,
        isPendingReview,
        getQuestionResult,
        assignmentStatus,
        showCorrectAnswers,
        examIsActive,
    };
};

export default useExamResults;
