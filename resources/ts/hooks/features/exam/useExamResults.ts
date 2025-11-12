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
 */
const useExamResults = ({ exam, assignment, userAnswers }: UseExamResultParams) => {
    const questions = exam?.questions ?? [];
    const examIsActive = exam.is_active;
    const assignmentScore = assignment.score;
    const assignmentAutoScore = assignment.auto_score;
    const assignmentStatus = assignment.status;

    const totalPoints = useMemo(
        () => questions.reduce((sum, q) => sum + (q.points || 0), 0),
        [questions]
    );

    const finalScore = useMemo(
        () => assignmentScore ?? assignmentAutoScore,
        [assignmentScore, assignmentAutoScore]
    );

    const isPendingReview = useMemo(
        () => assignmentStatus !== "graded",
        [assignmentStatus]
    );

    const formattedAssignmentStatus = useMemo(
        () => formatExamAssignmentStatus(assignmentStatus),
        [assignmentStatus]
    );

    const showCorrectAnswers = useMemo(
        () => canShowExamResults(assignmentStatus),
        [assignmentStatus]
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
                    const selectedChoices = userAnswer.choices.map((c: { choice: { id: number } }) => c.choice);
                    const correctChoices = (question.choices ?? []).filter(c => c.is_correct);

                    const selectedChoiceIds = new Set(selectedChoices.map((choice: { id: number }) => choice.id));
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
        assignmentStatus: formattedAssignmentStatus,
        showCorrectAnswers,
        examIsActive,
    };
};

export default useExamResults;
