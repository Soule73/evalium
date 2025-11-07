import { useCallback, useRef } from 'react';
import { route } from 'ziggy-js';

interface UseExamAnswerSaveOptions {
    examId: number;
}


/**
 * Custom React hook to manage saving exam answers, supporting both individual and batch save operations.
 *
 * This hook provides debounced saving of individual answers, immediate forced saving, and cleanup of pending save operations.
 * It is designed to be used in exam-taking interfaces where answers need to be periodically or immediately persisted.
 *
 * @param {UseExamAnswerSaveOptions} options - Options for the hook, including the `examId`.
 * @returns {{
 *   saveAnswerIndividual: (questionId: number, value: string | number | number[], allAnswers: Record<number, string | number | number[]>) => void;
 *   saveAllAnswers: (answers: Record<number, string | number | number[]>) => Promise<void>;
 *   forceSave: (answers: Record<number, string | number | number[]>) => Promise<void>;
 *   cleanup: () => void;
 * }} An object containing methods to save answers:
 *   - `saveAnswerIndividual`: Debounced save for a single answer.
 *   - `saveAllAnswers`: Immediately saves all answers.
 *   - `forceSave`: Forces immediate save, cancelling any pending debounced save.
 *   - `cleanup`: Cancels any pending save operations.
 *
 * @example
 * const { saveAnswerIndividual, saveAllAnswers, forceSave, cleanup } = useExamAnswerSave({ examId: 123 });
 * saveAnswerIndividual(1, "A", { 1: "A" });
 * await saveAllAnswers({ 1: "A", 2: "B" });
 * await forceSave({ 1: "A", 2: "B" });
 * cleanup();
 */
export function useExamAnswerSave({ examId }: UseExamAnswerSaveOptions): {
    saveAnswerIndividual: (questionId: number, value: string | number | number[], allAnswers: Record<number, string | number | number[]>) => void;
    saveAllAnswers: (answers: Record<number, string | number | number[]>) => Promise<void>;
    forceSave: (answers: Record<number, string | number | number[]>) => Promise<void>;
    cleanup: () => void;
} {
    const pendingSaveRef = useRef<NodeJS.Timeout | null>(null);
    const lastAnswersRef = useRef<Record<number, string | number | number[]>>({});

    const saveAllAnswers = useCallback(async (answers: Record<number, string | number | number[]>) => {
        try {
            // Transformer les données pour le backend
            const formattedAnswers: Record<number, string | number | number[]> = {};

            Object.entries(answers).forEach(([questionId, value]) => {
                const qId = parseInt(questionId);

                if (Array.isArray(value)) {
                    // Question multiple : s'assurer que ce sont des numbers
                    const choiceIds = value.filter(id => typeof id === 'number');
                    formattedAnswers[qId] = choiceIds;
                } else {
                    // Question simple
                    formattedAnswers[qId] = value;
                }
            });

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch(route('student.exams.save-answers', examId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    answers: formattedAnswers
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
        }
    }, [examId]);

    const saveAnswerIndividual = useCallback((_questionId: number, _value: string | number | number[], allAnswers: Record<number, string | number | number[]>) => {
        // Mettre à jour le ref avec les nouvelles réponses
        lastAnswersRef.current = { ...allAnswers };

        if (pendingSaveRef.current) {
            clearTimeout(pendingSaveRef.current);
        }

        pendingSaveRef.current = setTimeout(() => {
            // Utiliser directement allAnswers au lieu du ref qui pourrait être obsolète
            saveAllAnswers(allAnswers);
        }, 500);

    }, [saveAllAnswers]);

    const forceSave = useCallback(async (answers: Record<number, string | number | number[]>) => {
        if (pendingSaveRef.current) {
            clearTimeout(pendingSaveRef.current);
            pendingSaveRef.current = null;
        }

        await saveAllAnswers(answers);
    }, [saveAllAnswers]);

    const cleanup = useCallback(() => {
        if (pendingSaveRef.current) {
            clearTimeout(pendingSaveRef.current);
            pendingSaveRef.current = null;
        }
    }, []);

    return {
        saveAnswerIndividual,
        saveAllAnswers,
        forceSave,
        cleanup
    };
}