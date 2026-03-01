import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import type { Assessment, AssessmentAssignment, Answer, AssessmentRouteContext } from '@/types';
import { getAssessmentBackUrl } from '@/utils/assessment/routeUtils';

interface UseGradeStateParams {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    userAnswers: Record<number, Answer>;
    routeContext: AssessmentRouteContext;
}

interface UseGradeStateReturn {
    editableScores: Record<number, number>;
    setEditableScores: React.Dispatch<React.SetStateAction<Record<number, number>>>;
    feedbacks: Record<number, string>;
    teacherNotes: string;
    setTeacherNotes: React.Dispatch<React.SetStateAction<string>>;
    isSubmitting: boolean;
    showConfirmModal: boolean;
    setShowConfirmModal: React.Dispatch<React.SetStateAction<boolean>>;
    backUrl: string;
    saveGradeUrl: string;
    handleFeedbackChange: (questionId: number, value: string) => void;
    handleSubmit: () => void;
    handleConfirmSubmit: () => void;
}

/**
 * Manages all grading state and submission logic for the Grade page.
 *
 * Handles editable scores, per-question feedback, teacher notes,
 * submission confirmation flow, and back/save URL resolution.
 *
 * @param params - Assessment, assignment, user answers, and route context
 * @returns Grading state, setters, and action handlers
 */
export function useGradeState({
    assessment,
    assignment,
    userAnswers,
    routeContext,
}: UseGradeStateParams): UseGradeStateReturn {
    const [editableScores, setEditableScores] = useState<Record<number, number>>(() => {
        const scores: Record<number, number> = {};
        (assessment.questions ?? []).forEach((question) => {
            const answer = userAnswers[question.id];
            scores[question.id] = answer?.score ?? 0;
        });
        return scores;
    });

    const [feedbacks, setFeedbacks] = useState<Record<number, string>>(() => {
        const initial: Record<number, string> = {};
        (assessment.questions ?? []).forEach((question) => {
            const answer = userAnswers[question.id];
            if (answer?.feedback) {
                initial[question.id] = answer.feedback;
            }
        });
        return initial;
    });

    const [teacherNotes, setTeacherNotes] = useState(assignment.teacher_notes || '');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);

    const backUrl = getAssessmentBackUrl(routeContext, assessment);

    const saveGradeUrl = route(routeContext.saveGradeRoute, {
        assessment: assessment.id,
        assignment: assignment.id,
    });

    const handleFeedbackChange = useCallback((questionId: number, value: string) => {
        setFeedbacks((prev) => ({ ...prev, [questionId]: value }));
    }, []);

    const handleSubmit = useCallback(() => {
        setShowConfirmModal(true);
    }, []);

    const handleConfirmSubmit = useCallback(() => {
        setIsSubmitting(true);
        setShowConfirmModal(false);

        const scoresWithFeedback = (assessment.questions ?? []).map((question) => ({
            question_id: question.id,
            score: editableScores[question.id] || 0,
            feedback: feedbacks[question.id] || null,
        }));

        router.post(
            saveGradeUrl,
            {
                scores: scoresWithFeedback,
                teacher_notes: teacherNotes,
            },
            {
                onFinish: () => setIsSubmitting(false),
            },
        );
    }, [assessment, editableScores, feedbacks, teacherNotes, saveGradeUrl]);

    return {
        editableScores,
        setEditableScores,
        feedbacks,
        teacherNotes,
        setTeacherNotes,
        isSubmitting,
        showConfirmModal,
        setShowConfirmModal,
        backUrl,
        saveGradeUrl,
        handleFeedbackChange,
        handleSubmit,
        handleConfirmSubmit,
    };
}
