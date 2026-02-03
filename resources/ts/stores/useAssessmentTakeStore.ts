import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';

interface AssessmentTakeState {
  answers: Record<number, string | number | number[]>;
  isSubmitting: boolean;
  assessmentTerminated: boolean;
  terminationReason: string;
  timeLeft: number;
  showConfirmModal: boolean;
  showFullscreenModal: boolean;
  assessmentCanStart: boolean;
}

interface AssessmentTakeActions {
  setAnswer: (questionId: number, value: string | number | number[]) => void;
  setAnswers: (answers: Record<number, string | number | number[]>) => void;
  setIsSubmitting: (isSubmitting: boolean) => void;
  setAssessmentTerminated: (terminated: boolean, reason?: string) => void;
  setTimeLeft: (timeLeft: number | ((prev: number) => number)) => void;
  setShowConfirmModal: (show: boolean) => void;
  setShowFullscreenModal: (show: boolean) => void;
  setAssessmentCanStart: (canStart: boolean) => void;
  reset: () => void;
}

const initialState: AssessmentTakeState = {
  answers: {},
  isSubmitting: false,
  assessmentTerminated: false,
  terminationReason: '',
  timeLeft: 0,
  showConfirmModal: false,
  showFullscreenModal: false,
  assessmentCanStart: false,
};

export const useAssessmentTakeStore = create<AssessmentTakeState & AssessmentTakeActions>()(
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

    setAssessmentTerminated: (terminated, reason = '') => set((state) => {
      state.assessmentTerminated = terminated;
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

    setAssessmentCanStart: (canStart) => set((state) => {
      state.assessmentCanStart = canStart;
    }),

    reset: () => set(initialState),
  }))
);
