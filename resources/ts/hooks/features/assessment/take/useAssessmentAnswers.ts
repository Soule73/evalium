import { useEffect } from 'react';
import { type Answer, type Question } from '@/types';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { buildInitialAnswers } from '@/utils/assessment/take/answerUtils';

interface UseAssessmentAnswersParams {
    questions: Question[];
    userAnswers: Answer[];
}

/**
 * Custom hook to manage assessment answers state.
 * Initializes answers from existing userAnswers and provides update functionality.
 */
export const useAssessmentAnswers = ({ questions, userAnswers }: UseAssessmentAnswersParams) => {
    const { setAnswers, setAnswer } = useAssessmentTakeStore(
        useShallow((state) => ({
            setAnswers: state.setAnswers,
            setAnswer: state.setAnswer,
        })),
    );

    useEffect(() => {
        setAnswers(buildInitialAnswers(questions, userAnswers));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const updateAnswer = (questionId: number, value: string | number | number[]) => {
        setAnswer(questionId, value);
    };

    return {
        updateAnswer,
    };
};
