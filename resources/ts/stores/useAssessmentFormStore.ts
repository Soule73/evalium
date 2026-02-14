import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';
import { type QuestionFormData, type ChoiceFormData, type QuestionType } from '@/types';
import { createDefaultQuestion } from '@/utils/assessment';
import { arrayMove } from '@dnd-kit/sortable';

export interface DeletedQuestion {
  id: number;
  question: QuestionFormData;
  deletedAt: Date;
  index: number;
}

export interface DeletedChoice {
  id: number;
  choice: ChoiceFormData;
  questionId: number;
  questionIndex: number;
  choiceIndex: number;
  deletedAt: Date;
}

interface AssessmentFormState {
  questions: QuestionFormData[];
  deletedQuestionIds: number[];
  deletedChoiceIds: number[];
  deletedQuestionsHistory: DeletedQuestion[];
  deletedChoicesHistory: DeletedChoice[];

  setQuestions: (questions: QuestionFormData[]) => void;
  updateQuestion: (index: number, field: keyof QuestionFormData, value: QuestionFormData[keyof QuestionFormData]) => void;
  addQuestion: (type: QuestionType) => void;
  removeQuestion: (index: number) => void;
  reorderQuestions: (oldIndex: number, newIndex: number) => void;

  updateChoice: (questionIndex: number, choiceIndex: number, field: keyof ChoiceFormData, value: ChoiceFormData[keyof ChoiceFormData]) => void;
  addChoice: (questionIndex: number) => void;
  removeChoice: (questionIndex: number, choiceIndex: number) => void;

  addDeletedQuestionToHistory: (questionId: number, index: number, question: QuestionFormData) => void;
  addDeletedChoiceToHistory: (choiceId: number, questionIndex: number, choiceIndex: number, choice: ChoiceFormData, questionId: number) => void;
  restoreQuestion: (deletedQuestion: DeletedQuestion) => void;
  restoreChoice: (deletedChoice: DeletedChoice) => void;

  clearDeletedHistory: () => void;
  reset: () => void;
}

export const useAssessmentFormStore = create<AssessmentFormState>()(
  immer((set) => ({
    questions: [],
    deletedQuestionIds: [],
    deletedChoiceIds: [],
    deletedQuestionsHistory: [],
    deletedChoicesHistory: [],

    setQuestions: (questions) => set((state) => {
      state.questions = questions;
    }),

    updateQuestion: (index, field, value) => set((state) => {
      const question = state.questions[index];
      if (!question) return;

      Object.assign(question, { [field]: value });

      if (field === 'type' && value === 'boolean') {
        question.choices = [
          { content: 'true', is_correct: true, order_index: 1 },
          { content: 'false', is_correct: false, order_index: 2 }
        ];
      } else if (field === 'type' && question.type === 'boolean' && value !== 'boolean') {
        if (value === 'multiple' || value === 'one_choice') {
          question.choices = [
            { content: '', is_correct: true, order_index: 1 },
            { content: '', is_correct: false, order_index: 2 }
          ];
        } else if (value === 'text') {
          question.choices = [];
        }
      }
    }),

    addQuestion: (type) => set((state) => {
      const newQuestion = createDefaultQuestion(type, state.questions.length + 1);
      state.questions.push(newQuestion);
    }),

    removeQuestion: (index) => set((state) => {
      const question = state.questions[index];
      if (question?.id) {
        state.deletedQuestionIds.push(question.id);
      }
      state.questions.splice(index, 1);
    }),

    reorderQuestions: (oldIndex, newIndex) => set((state) => {
      const reordered = arrayMove(state.questions, oldIndex, newIndex);
      state.questions = reordered.map((q, index) => ({
        ...q,
        order_index: index + 1
      }));
    }),

    updateChoice: (questionIndex, choiceIndex, field, value) => set((state) => {
      const question = state.questions[questionIndex];
      if (!question?.choices?.[choiceIndex]) return;

      const choice = question.choices[choiceIndex];
      Object.assign(choice, { [field]: value });

      if (field === 'is_correct') {
        if (question.type === 'one_choice' && value === true) {
          question.choices.forEach((c, i) => {
            if (i !== choiceIndex) {
              c.is_correct = false;
            }
          });
        } else if (question.type === 'boolean') {
          question.choices.forEach((c, i) => {
            c.is_correct = i === choiceIndex;
          });
        }
      }
    }),

    addChoice: (questionIndex) => set((state) => {
      const question = state.questions[questionIndex];
      if (!question) return;

      const newChoice: ChoiceFormData = {
        content: '',
        is_correct: false,
        order_index: (question.choices?.length || 0) + 1
      };
      if (!question.choices) {
        question.choices = [];
      }
      question.choices.push(newChoice);
    }),

    removeChoice: (questionIndex, choiceIndex) => set((state) => {
      const question = state.questions[questionIndex];
      if (!question?.choices) return;

      const choice = question.choices[choiceIndex];
      if (choice?.id && question.id) {
        state.deletedChoiceIds.push(choice.id);
      }
      question.choices.splice(choiceIndex, 1);
    }),

    addDeletedQuestionToHistory: (questionId, index, question) => set((state) => {
      state.deletedQuestionsHistory.push({
        id: questionId,
        question,
        deletedAt: new Date(),
        index
      });
    }),

    addDeletedChoiceToHistory: (choiceId, questionIndex, choiceIndex, choice, questionId) => set((state) => {
      state.deletedChoicesHistory.push({
        id: choiceId,
        choice,
        questionId,
        questionIndex,
        choiceIndex,
        deletedAt: new Date()
      });
    }),

    restoreQuestion: (deletedQuestion) => set((state) => {
      state.questions.splice(deletedQuestion.index, 0, deletedQuestion.question);
      state.deletedQuestionIds = state.deletedQuestionIds.filter(id => id !== deletedQuestion.id);
      state.deletedQuestionsHistory = state.deletedQuestionsHistory.filter(
        item => item.id !== deletedQuestion.id
      );
    }),

    restoreChoice: (deletedChoice) => set((state) => {
      const question = state.questions[deletedChoice.questionIndex];
      if (question?.choices) {
        question.choices.splice(deletedChoice.choiceIndex, 0, deletedChoice.choice);
        state.deletedChoiceIds = state.deletedChoiceIds.filter(id => id !== deletedChoice.id);
        state.deletedChoicesHistory = state.deletedChoicesHistory.filter(
          item => item.id !== deletedChoice.id
        );
      }
    }),

    clearDeletedHistory: () => set((state) => {
      state.deletedQuestionsHistory = [];
      state.deletedChoicesHistory = [];
    }),

    reset: () => set({
      questions: [],
      deletedQuestionIds: [],
      deletedChoiceIds: [],
      deletedQuestionsHistory: [],
      deletedChoicesHistory: []
    })
  }))
);
