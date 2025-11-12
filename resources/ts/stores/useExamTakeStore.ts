import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';

interface ExamTakeState {
    answers: Record<number, string | number | number[]>;
    isSubmitting: boolean;
    examTerminated: boolean;
    terminationReason: string;
    timeLeft: number;
    showConfirmModal: boolean;
    showFullscreenModal: boolean;
    examCanStart: boolean;
}

interface ExamTakeActions {
    setAnswer: (questionId: number, value: string | number | number[]) => void;
    setAnswers: (answers: Record<number, string | number | number[]>) => void;
    setIsSubmitting: (isSubmitting: boolean) => void;
    setExamTerminated: (terminated: boolean, reason?: string) => void;
    setTimeLeft: (timeLeft: number | ((prev: number) => number)) => void;
    setShowConfirmModal: (show: boolean) => void;
    setShowFullscreenModal: (show: boolean) => void;
    setExamCanStart: (canStart: boolean) => void;
    reset: () => void;
}

const initialState: ExamTakeState = {
    answers: {},
    isSubmitting: false,
    examTerminated: false,
    terminationReason: '',
    timeLeft: 0,
    showConfirmModal: false,
    showFullscreenModal: false,
    examCanStart: false,
};

export const useExamTakeStore = create<ExamTakeState & ExamTakeActions>()(
    immer((set) => ({
        ...initialState,

        setAnswer: (questionId, value) => set((state) => {
            state.answers[questionId] = value;
        }),

        setAnswers: (answers) => set((state) => {
            state.answers = answers;
        }),

        setIsSubmitting: (isSubmitting) => set((state) => {
            state.isSubmitting = isSubmitting;
        }),

        setExamTerminated: (terminated, reason = '') => set((state) => {
            state.examTerminated = terminated;
            state.terminationReason = reason;
        }),

        setTimeLeft: (timeLeft) => set((state) => {
            state.timeLeft = typeof timeLeft === 'function' ? timeLeft(state.timeLeft) : timeLeft;
        }),

        setShowConfirmModal: (show) => set((state) => {
            state.showConfirmModal = show;
        }),

        setShowFullscreenModal: (show) => set((state) => {
            state.showFullscreenModal = show;
        }),

        setExamCanStart: (canStart) => set((state) => {
            state.examCanStart = canStart;
        }),

        reset: () => set(initialState),
    }))
);
