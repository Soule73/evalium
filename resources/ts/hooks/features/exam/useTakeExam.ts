import { useEffect } from "react";
import { Answer, Exam, Question } from "@/types";
import { useExamSecurity } from './useExamSecurity';
import { useAutoSave } from './useAutoSave';
import { useExamTimer } from './useExamTimer';
import { useExamAnswers } from './useExamAnswers';
import { useExamFullscreen } from './useExamFullscreen';
import { useExamSecurityViolation } from './useExamSecurityViolation';
import { useExamAnswerSave } from './useExamAnswerSave';
import { useExamSubmission } from './useExamSubmission';

interface UseTakeExam {
    exam: Exam
    questions: Question[];
    userAnswers: Answer[];
}

/**
 * Custom React hook to manage the process of taking an exam.
 *
 * This hook encapsulates the logic for handling exam answers, submission, security,
 * fullscreen requirements, timing, auto-saving, and cleanup. It coordinates multiple
 * sub-hooks to provide a unified interface for the exam-taking experience.
 *
 * @param params - An object containing:
 *   @param exam - The exam object containing exam details.
 *   @param questions - An array of questions for the exam.
 *   @param userAnswers - An array of the user's existing answers (optional).
 *
 * @returns An object with the following properties and handlers:
 * - `answers`: The current state of the user's answers.
 * - `isSubmitting`: Boolean indicating if the exam is being submitted.
 * - `showConfirmModal`: Boolean to control the visibility of the confirmation modal.
 * - `setShowConfirmModal`: Function to set the confirmation modal visibility.
 * - `timeLeft`: Remaining time for the exam.
 * - `security`: Security-related handlers and state.
 * - `processing`: Boolean indicating if a process (e.g., submission) is ongoing.
 * - `handleAnswerChange`: Function to update an answer for a question.
 * - `handleSubmit`: Function to submit the exam.
 * - `handleAbandon`: Function to abandon the exam.
 * - `autoSave`: Auto-save handler and state.
 * - `examTerminated`: Boolean indicating if the exam was terminated due to a violation.
 * - `terminationReason`: Reason for exam termination, if any.
 * - `showFullscreenModal`: Boolean to control the fullscreen modal visibility.
 * - `fullscreenRequired`: Boolean indicating if fullscreen is required.
 * - `enterFullscreen`: Function to enter fullscreen mode.
 * - `examCanStart`: Boolean indicating if the exam can be started.
 *
 * @example
 * const {
 *   answers,
 *   handleAnswerChange,
 *   handleSubmit,
 *   timeLeft,
 *   ...
 * } = useTakeExam({ exam, questions, userAnswers });
 */
const useTakeExam = (
    { exam, questions = [], userAnswers = [] }: UseTakeExam
) => {
    const { answers, updateAnswer } = useExamAnswers({ questions, userAnswers });

    const {
        isSubmitting,
        showConfirmModal,
        setShowConfirmModal,
        processing,
        handleSubmit: submitExam,
        handleAbandon,
        updateSubmissionData
    } = useExamSubmission({
        examId: exam.id,
        onSubmitSuccess: () => {
            exitFullscreen();
        },
        onSubmitError: () => {
            exitFullscreen();
        }
    });

    const {
        examTerminated,
        terminationReason,
        handleViolation,
    } = useExamSecurityViolation({
        examId: exam.id
    });

    const security = useExamSecurity({
        onViolation: (type: string) => {
            handleViolation(type, answers);
        }
    });

    const {
        showFullscreenModal,
        fullscreenRequired,
        examCanStart,
        enterFullscreen,
        exitFullscreen
    } = useExamFullscreen({ security });

    const { timeLeft } = useExamTimer({
        duration: exam.duration,
        onTimeEnd: () => handleSubmit(),
        isSubmitting
    });

    const { saveAnswerIndividual, saveAllAnswers, forceSave, cleanup } = useExamAnswerSave({
        examId: exam.id
    });

    const autoSave = useAutoSave(answers, {
        interval: 30000,
        onSave: saveAllAnswers,
        onError: () => {
        }
    });

    useEffect(() => {
        updateSubmissionData(answers);
    }, [answers, updateSubmissionData]);

    useEffect(() => {
        if (examTerminated) {
            exitFullscreen();
        }
    }, [examTerminated, exitFullscreen]);

    const handleAnswerChange = (questionId: number, value: string | number | number[]) => {
        updateAnswer(questionId, value);

        const newAnswers = { ...answers, [questionId]: value };

        saveAnswerIndividual(questionId, value, newAnswers);
    };

    const handleSubmit = () => {
        forceSave(answers).then(() => {
            submitExam(answers);
        });
    };

    useEffect(() => {
        return cleanup;
    }, [cleanup]);

    return {
        answers,
        isSubmitting,
        showConfirmModal,
        setShowConfirmModal,
        timeLeft,
        security,
        processing,
        handleAnswerChange,
        handleSubmit,
        handleAbandon,
        autoSave,
        examTerminated,
        terminationReason,
        showFullscreenModal,
        fullscreenRequired,
        enterFullscreen,
        examCanStart
    };
};

export default useTakeExam;