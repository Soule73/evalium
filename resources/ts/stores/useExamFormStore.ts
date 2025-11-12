import { create } from 'zustand';
import { immer } from 'zustand/middleware/immer';
import { QuestionFormData, ChoiceFormData, QuestionType } from '@/types';
import { createDefaultQuestion } from '@/utils/exam';
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

interface ExamFormState {
    questions: QuestionFormData[];
    deletedQuestionIds: number[];
    deletedChoiceIds: number[];
    deletedQuestionsHistory: DeletedQuestion[];
    deletedChoicesHistory: DeletedChoice[];

    setQuestions: (questions: QuestionFormData[]) => void;
    updateQuestion: (index: number, field: keyof QuestionFormData, value: any) => void;
    addQuestion: (type: QuestionType) => void;
    removeQuestion: (index: number) => void;
    reorderQuestions: (oldIndex: number, newIndex: number) => void;

    updateChoice: (questionIndex: number, choiceIndex: number, field: keyof ChoiceFormData, value: any) => void;
    addChoice: (questionIndex: number) => void;
    removeChoice: (questionIndex: number, choiceIndex: number) => void;

    addDeletedQuestionToHistory: (questionId: number, index: number, question: QuestionFormData) => void;
    addDeletedChoiceToHistory: (choiceId: number, questionIndex: number, choiceIndex: number, choice: ChoiceFormData, questionId: number) => void;
    restoreQuestion: (deletedQuestion: DeletedQuestion) => void;
    restoreChoice: (deletedChoice: DeletedChoice) => void;

    clearDeletedHistory: () => void;
    reset: () => void;
}

export const useExamFormStore = create<ExamFormState>()(
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

            (question as any)[field] = value;

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
            const choice = question?.choices[choiceIndex];
            if (!choice) return;

            (choice as any)[field] = value;

            if (field === 'is_correct' && value && question.type === 'one_choice') {
                question.choices.forEach((c, i) => {
                    if (i !== choiceIndex) {
                        c.is_correct = false;
                    }
                });
            }
        }),

        addChoice: (questionIndex) => set((state) => {
            const question = state.questions[questionIndex];
            if (question) {
                const newChoice: ChoiceFormData = {
                    content: '',
                    is_correct: false,
                    order_index: question.choices.length + 1
                };
                question.choices.push(newChoice);
            }
        }),

        removeChoice: (questionIndex, choiceIndex) => set((state) => {
            const question = state.questions[questionIndex];
            if (!question || question.choices.length <= 2) return;

            const choice = question.choices[choiceIndex];
            if (choice?.id) {
                state.deletedChoiceIds.push(choice.id);
            }

            question.choices.splice(choiceIndex, 1);
            question.choices.forEach((c, i) => {
                c.order_index = i + 1;
            });
        }),

        addDeletedQuestionToHistory: (questionId, index, question) => set((state) => {
            state.deletedQuestionsHistory.push({
                id: questionId,
                question: { ...question },
                deletedAt: new Date(),
                index
            });
        }),

        addDeletedChoiceToHistory: (choiceId, questionIndex, choiceIndex, choice, questionId) => set((state) => {
            state.deletedChoicesHistory.push({
                id: choiceId,
                choice: { ...choice },
                questionId,
                questionIndex,
                choiceIndex,
                deletedAt: new Date()
            });
        }),

        restoreQuestion: (deletedQuestion) => set((state) => {
            const insertIndex = Math.min(deletedQuestion.index, state.questions.length);

            const restoredQuestion = {
                ...deletedQuestion.question,
                choices: deletedQuestion.question.choices.map(choice => ({ ...choice }))
            };

            state.questions.splice(insertIndex, 0, restoredQuestion);

            state.questions.forEach((q, index) => {
                q.order_index = index + 1;
            });

            state.deletedQuestionsHistory = state.deletedQuestionsHistory.filter(
                dq => dq.id !== deletedQuestion.id
            );
        }),

        restoreChoice: (deletedChoice) => set((state) => {
            const questionIndex = state.questions.findIndex(q => q.id === deletedChoice.questionId);
            if (questionIndex === -1) return;

            const question = state.questions[questionIndex];
            const insertIndex = Math.min(deletedChoice.choiceIndex, question.choices.length);

            const restoredChoice = { ...deletedChoice.choice };

            question.choices.splice(insertIndex, 0, restoredChoice);

            question.choices.forEach((c, index) => {
                c.order_index = index + 1;
            });

            state.deletedChoicesHistory = state.deletedChoicesHistory.filter(
                dc => dc.id !== deletedChoice.id
            );
        }),

        clearDeletedHistory: () => set((state) => {
            state.deletedQuestionIds = [];
            state.deletedChoiceIds = [];
            state.deletedQuestionsHistory = [];
            state.deletedChoicesHistory = [];
        }),

        reset: () => set((state) => {
            state.questions = [];
            state.deletedQuestionIds = [];
            state.deletedChoiceIds = [];
            state.deletedQuestionsHistory = [];
            state.deletedChoicesHistory = [];
        }),
    }))
);

