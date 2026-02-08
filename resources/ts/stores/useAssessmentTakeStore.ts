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
  currentQuestionIndex: number;
  shuffledQuestionIds: number[];
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
  setCurrentQuestionIndex: (index: number) => void;
  setShuffledQuestionIds: (ids: number[]) => void;
  goToNextQuestion: (totalQuestions: number) => void;
  goToPreviousQuestion: () => void;
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
  currentQuestionIndex: 0,
  shuffledQuestionIds: [],
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

    setCurrentQuestionIndex: (index) => set((state) => {
      state.currentQuestionIndex = index;
    }),

    setShuffledQuestionIds: (ids) => set((state) => {
      state.shuffledQuestionIds = ids;
    }),

    goToNextQuestion: (totalQuestions) => set((state) => {
      if (state.currentQuestionIndex < totalQuestions - 1) {
        state.currentQuestionIndex += 1;
      }
    }),

    goToPreviousQuestion: () => set((state) => {
      if (state.currentQuestionIndex > 0) {
        state.currentQuestionIndex -= 1;
      }
    }),

    reset: () => set(initialState),
  }))
);
