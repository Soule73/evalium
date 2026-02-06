import { useState, useCallback } from 'react';
import { DragEndEvent } from '@dnd-kit/core';
import { useShallow } from 'zustand/react/shallow';
import { QuestionFormData, ChoiceFormData, QuestionType } from '@/types';
import { useAssessmentFormStore } from '@/stores';
import { getQuestionTypeIcon as getIcon } from '@/utils/assessment';

export const useQuestionsManager = () => {
    const [showAddDropdown, setShowAddDropdown] = useState(false);
    const [collapsedQuestions, setCollapsedQuestions] = useState<Set<string>>(new Set());

    const {
        questions,
        addQuestion: storeAddQuestion,
        removeQuestion,
        updateQuestion,
        reorderQuestions,
        addChoice,
        removeChoice,
        updateChoice,
        addDeletedQuestionToHistory,
        addDeletedChoiceToHistory,
        deletedQuestionsHistory,
        deletedChoicesHistory,
        restoreQuestion,
        restoreChoice,
        clearDeletedHistory,
    } = useAssessmentFormStore(useShallow((state) => ({
        questions: state.questions,
        addQuestion: state.addQuestion,
        removeQuestion: state.removeQuestion,
        updateQuestion: state.updateQuestion,
        reorderQuestions: state.reorderQuestions,
        addChoice: state.addChoice,
        removeChoice: state.removeChoice,
        updateChoice: state.updateChoice,
        addDeletedQuestionToHistory: state.addDeletedQuestionToHistory,
        addDeletedChoiceToHistory: state.addDeletedChoiceToHistory,
        deletedQuestionsHistory: state.deletedQuestionsHistory,
        deletedChoicesHistory: state.deletedChoicesHistory,
        restoreQuestion: state.restoreQuestion,
        restoreChoice: state.restoreChoice,
        clearDeletedHistory: state.clearDeletedHistory,
    })));

    const [confirmationModal, setConfirmationModal] = useState<{
        isOpen: boolean;
        type: 'question' | 'choice';
        title: string;
        message: string;
        onConfirm: () => void;
    }>({
        isOpen: false,
        type: 'question',
        title: '',
        message: '',
        onConfirm: () => { }
    });

    const [historyModalOpen, setHistoryModalOpen] = useState(false);

    const handleRequestQuestionDeletion = useCallback((index: number, question: QuestionFormData) => {
        setConfirmationModal({
            isOpen: true,
            type: 'question',
            title: 'Confirmer la suppression',
            message: `Êtes-vous sûr de vouloir supprimer cette question ?`,
            onConfirm: () => {
                confirmQuestionDeletion(index, question);
                setConfirmationModal(prev => ({ ...prev, isOpen: false }));
            }
        });
    }, []);

    const confirmQuestionDeletion = useCallback((index: number, question: QuestionFormData) => {
        if (question.id) {
            addDeletedQuestionToHistory(question.id, index, question);
        }
        removeQuestion(index);
    }, [removeQuestion, addDeletedQuestionToHistory]);

    const handleRequestChoiceDeletion = useCallback((questionIndex: number, choiceIndex: number, question: QuestionFormData, choice: ChoiceFormData) => {
        setConfirmationModal({
            isOpen: true,
            type: 'choice',
            title: 'Confirmer la suppression',
            message: `Êtes-vous sûr de vouloir supprimer ce choix ?`,
            onConfirm: () => {
                confirmChoiceDeletion(questionIndex, choiceIndex, question, choice);
                setConfirmationModal(prev => ({ ...prev, isOpen: false }));
            }
        });
    }, []);

    const confirmChoiceDeletion = useCallback((questionIndex: number, choiceIndex: number, question: QuestionFormData, choice: ChoiceFormData) => {
        if (choice.id) {
            addDeletedChoiceToHistory(choice.id, questionIndex, choiceIndex, choice, question.id || 0);
        }
        removeChoice(questionIndex, choiceIndex);
    }, [removeChoice, addDeletedChoiceToHistory]);

    const handleDragEnd = useCallback((event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = questions.findIndex((_, index) => index.toString() === active.id);
            const newIndex = questions.findIndex((_, index) => index.toString() === over.id);
            reorderQuestions(oldIndex, newIndex);
        }
    }, [questions, reorderQuestions]);

    const toggleAddDropdown = useCallback(() => {
        setShowAddDropdown(!showAddDropdown);
    }, [showAddDropdown]);

    const getQuestionTypeIcon = getIcon;

    const handleAddQuestion = useCallback((type: QuestionType) => {
        storeAddQuestion(type);
        setShowAddDropdown(false);
    }, [storeAddQuestion]);

    const handleRemoveQuestion = useCallback((index: number) => {
        const question = questions[index];
        if (question.id) {
            handleRequestQuestionDeletion(index, question);
        } else {
            removeQuestion(index);
        }
    }, [questions, removeQuestion, handleRequestQuestionDeletion]);

    const handleRemoveChoice = useCallback((questionIndex: number, choiceIndex: number) => {
        const question = questions[questionIndex];
        const choice = question?.choices[choiceIndex];

        if (choice?.id) {
            handleRequestChoiceDeletion(questionIndex, choiceIndex, question, choice);
        } else {
            removeChoice(questionIndex, choiceIndex);
        }
    }, [questions, removeChoice, handleRequestChoiceDeletion]);

    const toggleCollapse = useCallback((index: number) => {
        setCollapsedQuestions(prev => {
            const newCollapsed = new Set(prev);
            const questionKey = `question-${index}`;
            if (newCollapsed.has(questionKey)) {
                newCollapsed.delete(questionKey);
            } else {
                newCollapsed.add(questionKey);
            }
            return newCollapsed;
        });
    }, []);

    const hasDeletedItems = useCallback(() => {
        return deletedQuestionsHistory.length > 0 || deletedChoicesHistory.length > 0;
    }, [deletedQuestionsHistory, deletedChoicesHistory]);

    const getDeletedQuestionsCount = useCallback(() => {
        return deletedQuestionsHistory.length;
    }, [deletedQuestionsHistory]);

    const getDeletedChoicesCount = useCallback(() => {
        return deletedChoicesHistory.length;
    }, [deletedChoicesHistory]);

    return {
        showAddDropdown,
        collapsedQuestions,
        handleDragEnd,
        toggleAddDropdown,
        getQuestionTypeIcon,
        addQuestion: handleAddQuestion,
        removeQuestion: handleRemoveQuestion,
        updateQuestion,
        addChoice,
        removeChoice: handleRemoveChoice,
        updateChoice,
        toggleCollapse,
        deleteHistory: {
            deletedQuestions: deletedQuestionsHistory,
            deletedChoices: deletedChoicesHistory,
            restoreQuestion,
            restoreChoice,
            clearHistory: clearDeletedHistory,
            hasDeletedItems,
            getDeletedQuestionsCount,
            getDeletedChoicesCount,
        },
        confirmationModal,
        historyModalOpen,
        setHistoryModalOpen,
        setConfirmationModal
    };
};