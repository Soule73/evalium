import { createContext, useContext, useMemo } from 'react';
import type { Answer } from '@/types';
import {
    type QuestionRenderMode,
    type QuestionViewerRole,
    type QuestionRenderConfig,
    type AnswerValue,
    buildRenderConfig,
} from '@/types';

interface QuestionContextValue {
    config: QuestionRenderConfig;
    isDisabled: boolean;
    userAnswers: Record<number, Answer>;
    answers: Record<number, AnswerValue>;
    onAnswerChange?: (questionId: number, value: AnswerValue) => void;
    assessmentId?: number;
    fileAnswers?: Record<number, Answer>;
    onFileAnswerSaved?: (questionId: number, answer: Answer) => void;
    onFileAnswerRemoved?: (questionId: number, answerId: number) => void;
    scoreOverrides?: Record<number, number>;
    onScoreChange?: (questionId: number, value: number) => void;
    feedbackOverrides?: Record<number, string>;
    onFeedbackChange?: (questionId: number, value: string) => void;
}

const QuestionContext = createContext<QuestionContextValue | null>(null);

/**
 * Reads the nearest QuestionContext. Throws if called outside a QuestionProvider.
 */
// eslint-disable-next-line react-refresh/only-export-components
export function useQuestionContext(): QuestionContextValue {
    const ctx = useContext(QuestionContext);
    if (!ctx) {
        throw new Error('useQuestionContext must be used inside <QuestionProvider>');
    }
    return ctx;
}

interface QuestionProviderProps {
    mode: QuestionRenderMode;
    role: QuestionViewerRole;
    children: React.ReactNode;
    isDisabled?: boolean;
    userAnswers?: Record<number, Answer>;
    showCorrectAnswers?: boolean;
    canEditScores?: boolean;
    answers?: Record<number, AnswerValue>;
    onAnswerChange?: (questionId: number, value: AnswerValue) => void;
    assessmentId?: number;
    fileAnswers?: Record<number, Answer>;
    onFileAnswerSaved?: (questionId: number, answer: Answer) => void;
    onFileAnswerRemoved?: (questionId: number, answerId: number) => void;
    scoreOverrides?: Record<number, number>;
    onScoreChange?: (questionId: number, value: number) => void;
    feedbackOverrides?: Record<number, string>;
    onFeedbackChange?: (questionId: number, value: string) => void;
}

/**
 * Provides rendering configuration and answer data to all descendant QuestionCard components.
 * Place one Provider per page, wrapping the question list.
 */
export function QuestionProvider({
    mode,
    role,
    children,
    isDisabled = false,
    userAnswers = {},
    showCorrectAnswers,
    canEditScores,
    answers = {},
    onAnswerChange,
    assessmentId,
    fileAnswers,
    onFileAnswerSaved,
    onFileAnswerRemoved,
    scoreOverrides,
    onScoreChange,
    feedbackOverrides,
    onFeedbackChange,
}: QuestionProviderProps) {
    const config = useMemo(
        () => buildRenderConfig(mode, role, { canEditScores, showCorrectAnswers }),
        [mode, role, canEditScores, showCorrectAnswers],
    );

    const value = useMemo<QuestionContextValue>(
        () => ({
            config,
            isDisabled,
            userAnswers,
            answers,
            onAnswerChange,
            assessmentId,
            fileAnswers,
            onFileAnswerSaved,
            onFileAnswerRemoved,
            scoreOverrides,
            onScoreChange,
            feedbackOverrides,
            onFeedbackChange,
        }),
        [
            config,
            isDisabled,
            userAnswers,
            answers,
            onAnswerChange,
            assessmentId,
            fileAnswers,
            onFileAnswerSaved,
            onFileAnswerRemoved,
            scoreOverrides,
            onScoreChange,
            feedbackOverrides,
            onFeedbackChange,
        ],
    );

    return <QuestionContext.Provider value={value}>{children}</QuestionContext.Provider>;
}
