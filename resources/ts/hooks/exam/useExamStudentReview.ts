import { useState } from 'react';
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

    const { assignmentStatus } = useExamResults({ exam, assignment, userAnswers });
    const { totalPoints, calculateQuestionScore, getQuestionResult } = useExamScoring({ exam, assignment, userAnswers });

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
    const [feedbacks, setFeedbacks] = useState<Record<number, string>>(() => {
        const initialFeedbacks: Record<number, string> = {};
        (exam.questions || []).forEach(question => {
            const userAnswer = userAnswers[question.id];
            if (userAnswer && userAnswer.feedback) {
                initialFeedbacks[question.id] = userAnswer.feedback;
            }
        });
        return initialFeedbacks;
    });

    const handleSubmit = async () => {
        setShowConfirmModal(true);
    };

    const handleConfirmSubmit = async () => {
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
    };

    const handleCancelSubmit = () => {
        setShowConfirmModal(false);
    };

    const handleFeedbackChange = (questionId: number, feedback: string) => {
        setFeedbacks(prev => ({
            ...prev,
            [questionId]: feedback
        }));
    };

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