import { useState, useCallback } from 'react';
import { type DragEndEvent } from '@dnd-kit/core';
import { useShallow } from 'zustand/react/shallow';
import { type QuestionFormData, type ChoiceFormData, type QuestionType } from '@/types';
import { useAssessmentFormStore } from '@/stores';
import { getQuestionTypeIcon as getIcon } from '@/utils/assessment';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useConfirmationModal } from '@/hooks/features/shared/useConfirmationModal';

interface ConfirmationData {
    type: 'question' | 'choice';
    title: string;
    message: string;
    onConfirm: () => void;
}

export const useQuestionsManager = () => {
    const { t } = useTranslations();
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
    } = useAssessmentFormStore(
        useShallow((state) => ({
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
        })),
    );

    const confirmationModal = useConfirmationModal<ConfirmationData>();

    const [historyModalOpen, setHistoryModalOpen] = useState(false);

    const confirmQuestionDeletion = useCallback(
        (index: number, question: QuestionFormData) => {
            if (question.id) {
                addDeletedQuestionToHistory(question.id, index, question);
            }
            removeQuestion(index);
        },
        [removeQuestion, addDeletedQuestionToHistory],
    );

    const handleRequestQuestionDeletion = useCallback(
        (index: number, question: QuestionFormData) => {
            confirmationModal.openModal({
                type: 'question',
                title: t('components.questions_manager.confirm_delete_title'),
                message: t('components.questions_manager.confirm_delete_question_message'),
                onConfirm: () => {
                    confirmQuestionDeletion(index, question);
                    confirmationModal.closeModal();
                },
            });
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [confirmQuestionDeletion, confirmationModal],
    );

    const confirmChoiceDeletion = useCallback(
        (
            questionIndex: number,
            choiceIndex: number,
            question: QuestionFormData,
            choice: ChoiceFormData,
        ) => {
            if (choice.id) {
                addDeletedChoiceToHistory(
                    choice.id,
                    questionIndex,
                    choiceIndex,
                    choice,
                    question.id || 0,
                );
            }
            removeChoice(questionIndex, choiceIndex);
        },
        [removeChoice, addDeletedChoiceToHistory],
    );

    const handleRequestChoiceDeletion = useCallback(
        (
            questionIndex: number,
            choiceIndex: number,
            question: QuestionFormData,
            choice: ChoiceFormData,
        ) => {
            confirmationModal.openModal({
                type: 'choice',
                title: t('components.questions_manager.confirm_delete_title'),
                message: t('components.questions_manager.confirm_delete_choice_message'),
                onConfirm: () => {
                    confirmChoiceDeletion(questionIndex, choiceIndex, question, choice);
                    confirmationModal.closeModal();
                },
            });
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [confirmChoiceDeletion, confirmationModal],
    );

    const handleDragEnd = useCallback(
        (event: DragEndEvent) => {
            const { active, over } = event;

            if (over && active.id !== over.id) {
                const oldIndex = questions.findIndex((_, index) => index.toString() === active.id);
                const newIndex = questions.findIndex((_, index) => index.toString() === over.id);
                reorderQuestions(oldIndex, newIndex);
            }
        },
        [questions, reorderQuestions],
    );

    const toggleAddDropdown = useCallback(() => {
        setShowAddDropdown(!showAddDropdown);
    }, [showAddDropdown]);

    const getQuestionTypeIcon = getIcon;

    const handleAddQuestion = useCallback(
        (type: QuestionType) => {
            storeAddQuestion(type);
            setShowAddDropdown(false);
        },
        [storeAddQuestion],
    );

    const handleRemoveQuestion = useCallback(
        (index: number) => {
            const question = questions[index];
            if (question.id) {
                handleRequestQuestionDeletion(index, question);
            } else {
                removeQuestion(index);
            }
        },
        [questions, removeQuestion, handleRequestQuestionDeletion],
    );

    const handleRemoveChoice = useCallback(
        (questionIndex: number, choiceIndex: number) => {
            const question = questions[questionIndex];
            const choice = question?.choices[choiceIndex];

            if (choice?.id) {
                handleRequestChoiceDeletion(questionIndex, choiceIndex, question, choice);
            } else {
                removeChoice(questionIndex, choiceIndex);
            }
        },
        [questions, removeChoice, handleRequestChoiceDeletion],
    );

    const toggleCollapse = useCallback((index: number) => {
        setCollapsedQuestions((prev) => {
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
        confirmationModal: {
            isOpen: confirmationModal.isOpen,
            data: confirmationModal.data,
            closeModal: confirmationModal.closeModal,
        },
        historyModalOpen,
        setHistoryModalOpen,
    };
};
