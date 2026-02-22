import { useCallback, useRef, useState } from 'react';
import { route } from 'ziggy-js';
import axios from 'axios';

type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';

interface UseAssessmentAnswerSaveParams {
    assessmentId: number;
}

/**
 * Custom hook to handle saving assessment answers to the server.
 * Provides methods for saving individual answers or all answers at once.
 */
export const useAssessmentAnswerSave = ({ assessmentId }: UseAssessmentAnswerSaveParams) => {
    const saveTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const savedTimeoutRef = useRef<NodeJS.Timeout | null>(null);
    const [saveStatus, setSaveStatus] = useState<SaveStatus>('idle');

    const markSaved = useCallback(() => {
        setSaveStatus('saved');
        if (savedTimeoutRef.current) {
            clearTimeout(savedTimeoutRef.current);
        }
        savedTimeoutRef.current = setTimeout(() => setSaveStatus('idle'), 3000);
    }, []);

    const saveAnswerIndividual = useCallback(
        (
            _questionId: number,
            _value: string | number | number[],
            allAnswers: Record<number, string | number | number[]>,
        ) => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
            }

            setSaveStatus('saving');

            saveTimeoutRef.current = setTimeout(() => {
                axios
                    .post(route('student.assessments.save-answers', assessmentId), {
                        answers: allAnswers,
                    })
                    .then(() => markSaved())
                    .catch(() => setSaveStatus('error'));
            }, 500);
        },
        [assessmentId, markSaved],
    );

    const saveAllAnswers = useCallback(
        async (answers: Record<number, string | number | number[]>) => {
            setSaveStatus('saving');
            try {
                await axios.post(route('student.assessments.save-answers', assessmentId), {
                    answers,
                });
                markSaved();
            } catch (error) {
                setSaveStatus('error');
                const message = error instanceof Error ? error.message : 'Unknown error';
                const wrappedError = new Error(`Failed to save answers: ${message}`);
                (wrappedError as unknown as Record<string, unknown>).cause = error;
                throw wrappedError;
            }
        },
        [assessmentId, markSaved],
    );

    const forceSave = useCallback(
        async (answers: Record<number, string | number | number[]>) => {
            if (saveTimeoutRef.current) {
                clearTimeout(saveTimeoutRef.current);
                saveTimeoutRef.current = null;
            }
            return saveAllAnswers(answers);
        },
        [saveAllAnswers],
    );

    const cleanup = useCallback(() => {
        if (saveTimeoutRef.current) {
            clearTimeout(saveTimeoutRef.current);
            saveTimeoutRef.current = null;
        }
        if (savedTimeoutRef.current) {
            clearTimeout(savedTimeoutRef.current);
            savedTimeoutRef.current = null;
        }
    }, []);

    return {
        saveAnswerIndividual,
        saveAllAnswers,
        forceSave,
        cleanup,
        saveStatus,
    };
};
