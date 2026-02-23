import { useEffect, useMemo } from 'react';
import { type Question } from '@/types';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { shuffleQuestions, reorderQuestionsByIds } from '@/utils/assessment/take';

interface UseQuestionNavigationParams {
    questions: Question[];
    shuffleEnabled: boolean;
    oneQuestionPerPage: boolean;
    enforceOnePerPage?: boolean;
}

/**
 * Hook to manage question navigation and shuffling for assessments.
 */
export function useQuestionNavigation({
    questions,
    shuffleEnabled,
    oneQuestionPerPage,
    enforceOnePerPage = false,
}: UseQuestionNavigationParams) {
    const effectiveOnePerPage = enforceOnePerPage || oneQuestionPerPage;
    const {
        currentQuestionIndex,
        shuffledQuestionIds,
        setCurrentQuestionIndex,
        setShuffledQuestionIds,
        goToNextQuestion,
        goToPreviousQuestion,
    } = useAssessmentTakeStore(
        useShallow((state) => ({
            currentQuestionIndex: state.currentQuestionIndex,
            shuffledQuestionIds: state.shuffledQuestionIds,
            setCurrentQuestionIndex: state.setCurrentQuestionIndex,
            setShuffledQuestionIds: state.setShuffledQuestionIds,
            goToNextQuestion: state.goToNextQuestion,
            goToPreviousQuestion: state.goToPreviousQuestion,
        })),
    );

    useEffect(() => {
        if (shuffleEnabled && questions.length > 0 && shuffledQuestionIds.length === 0) {
            const { questionIds } = shuffleQuestions(questions);
            setShuffledQuestionIds(questionIds);
        } else if (!shuffleEnabled && shuffledQuestionIds.length === 0 && questions.length > 0) {
            setShuffledQuestionIds(questions.map((q) => q.id));
        }
    }, [questions, shuffleEnabled, shuffledQuestionIds.length, setShuffledQuestionIds]);

    const orderedQuestions = useMemo(() => {
        if (shuffledQuestionIds.length === 0) {
            return questions;
        }
        return reorderQuestionsByIds(questions, shuffledQuestionIds);
    }, [questions, shuffledQuestionIds]);

    const displayedQuestions = useMemo(() => {
        if (!effectiveOnePerPage) {
            return orderedQuestions;
        }
        const currentQuestion = orderedQuestions[currentQuestionIndex];
        return currentQuestion ? [currentQuestion] : [];
    }, [orderedQuestions, effectiveOnePerPage, currentQuestionIndex]);

    const totalQuestions = orderedQuestions.length;
    const isFirstQuestion = currentQuestionIndex === 0;
    const isLastQuestion = currentQuestionIndex === totalQuestions - 1;

    const handleNextQuestion = () => {
        goToNextQuestion(totalQuestions);
    };

    const handlePreviousQuestion = () => {
        goToPreviousQuestion();
    };

    const goToQuestion = (index: number) => {
        if (index >= 0 && index < totalQuestions) {
            setCurrentQuestionIndex(index);
        }
    };

    return {
        displayedQuestions,
        orderedQuestions,
        currentQuestionIndex,
        totalQuestions,
        isFirstQuestion,
        isLastQuestion,
        handleNextQuestion,
        handlePreviousQuestion,
        goToQuestion,
        oneQuestionPerPage: effectiveOnePerPage,
    };
}
