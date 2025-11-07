import { useState, useCallback } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';

interface UseExamSubmissionOptions {
    examId: number;
    onSubmitSuccess?: () => void;
    onSubmitError?: () => void;
}

export function useExamSubmission({ examId, onSubmitSuccess, onSubmitError }: UseExamSubmissionOptions) {
    const [isSubmitting, setIsSubmitting] = useState<boolean>(false);
    const [showConfirmModal, setShowConfirmModal] = useState<boolean>(false);

    const submitForm = useForm({
        answers: {} as Record<number, string | number | number[]>
    });

    const abandonForm = useForm({});

    // Mettre à jour les données du formulaire quand les réponses changent
    const updateSubmissionData = useCallback((answers: Record<number, string | number | number[]>) => {
        submitForm.setData('answers', answers);
    }, [submitForm.setData]);

    // Soumission de l'examen
    const handleSubmit = useCallback(async (answers: Record<number, string | number | number[]>) => {
        if (isSubmitting) return;

        setIsSubmitting(true);

        submitForm.setData('answers', answers);
        submitForm.post(route('student.exams.submit', examId), {
            onSuccess: () => {
                setIsSubmitting(false);
                onSubmitSuccess?.();
            },
            onError: () => {
                setIsSubmitting(false);
                onSubmitError?.();
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    }, [examId, isSubmitting, submitForm, onSubmitSuccess, onSubmitError]);

    // Gérer l'abandon d'examen
    const handleAbandon = useCallback(() => {
        abandonForm.post(route('student.exams.abandon', examId), {
            onSuccess: () => {
                // Redirection automatique vers la liste des examens
            },
            onError: () => {
                // Erreur silencieuse, pas besoin d'alerter l'utilisateur
            }
        });
    }, [examId, abandonForm]);

    return {
        isSubmitting,
        showConfirmModal,
        setShowConfirmModal,
        processing: submitForm.processing,
        handleSubmit,
        handleAbandon,
        updateSubmissionData
    };
}