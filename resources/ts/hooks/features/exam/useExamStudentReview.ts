import { useState, useMemo, useCallback } from 'react';
import { route } from 'ziggy-js';
import useExamResults from './useExamResults';
import useExamScoring from './useExamScoring';
import { Answer, Exam, ExamAssignment, Group, User } from '@/types';
import useScoreManagement from './useScoreManagement';
import { router } from '@inertiajs/react';

interface ExamStudentReviewProps {
    exam: Exam;
    group?: Group;
    assignment: ExamAssignment;
    userAnswers: Record<number, Answer>;
    student: User;
}

const useExamStudentReview = (
    { exam, group, assignment, userAnswers, student }: ExamStudentReviewProps
) => {

    const { assignmentStatus, totalPoints, getQuestionResult } = useExamResults({ exam, assignment, userAnswers });
    const { calculateQuestionScore } = useExamScoring({
        exam,
        assignment,
        userAnswers,
        totalPoints,
        getQuestionResult
    });

    const {
        scores,
        calculatedTotalScore,
        percentage,
        handleScoreChange,
        getScoresForSave
    } = useScoreManagement({
        questions: exam.questions || [],
        userAnswers,
        calculateQuestionScore,
        totalPoints
    });

    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [teacherNotes, setTeacherNotes] = useState(assignment.teacher_notes || '');

    const initialFeedbacks = useMemo(() => {
        const feedbacks: Record<number, string> = {};
        (exam.questions || []).forEach(question => {
            const userAnswer = userAnswers[question.id];
            if (userAnswer?.feedback) {
                feedbacks[question.id] = userAnswer.feedback;
            }
        });
        return feedbacks;
    }, [exam.questions, userAnswers]);

    const [feedbacks, setFeedbacks] = useState<Record<number, string>>(initialFeedbacks);

    const handleSubmit = useCallback(() => {
        setShowConfirmModal(true);
    }, []);

    const handleConfirmSubmit = useCallback(() => {
        setIsSubmitting(true);
        setShowConfirmModal(false);

        try {
            const scoresWithFeedback = getScoresForSave().map(scoreData => ({
                ...scoreData,
                feedback: feedbacks[scoreData.question_id] || null
            }));

            router.post(route('exams.review.save', { exam: exam.id, student: student.id, group: group?.id }), {
                scores: scoresWithFeedback,
                teacher_notes: teacherNotes
            }, {
                onSuccess: () => {
                    setIsSubmitting(false);
                },
                onError: (_) => {
                    setIsSubmitting(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                }
            });
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
            setIsSubmitting(false);
        }
    }, [getScoresForSave, feedbacks, exam.id, student.id, group?.id, teacherNotes]);

    const handleCancelSubmit = useCallback(() => {
        setShowConfirmModal(false);
    }, []);

    const handleFeedbackChange = useCallback((questionId: number, feedback: string) => {
        setFeedbacks(prev => ({
            ...prev,
            [questionId]: feedback
        }));
    }, []);

    return {
        assignmentStatus,
        totalPoints,
        getQuestionResult,
        scores,
        calculatedTotalScore,
        percentage,
        handleScoreChange,
        isSubmitting,
        showConfirmModal,
        teacherNotes,
        setTeacherNotes,
        feedbacks,
        handleFeedbackChange,
        handleSubmit,
        handleConfirmSubmit,
        handleCancelSubmit
    };
}

export default useExamStudentReview;