import { useState } from 'react';
import { DragEndEvent } from '@dnd-kit/core';
import { arrayMove } from '@dnd-kit/sortable';
import { QuestionFormData, ChoiceFormData, QuestionType } from '@/types';
import { useDeleteHistory } from './useDeleteHistory';
import { getQuestionTypeIcon as getIcon, createDefaultQuestion } from '@/utils/exam';

interface UseQuestionsManagerProps {
    questions: QuestionFormData[];
    onQuestionsChange: (questions: QuestionFormData[]) => void;
    onQuestionDelete?: (questionId: number) => void;
    onChoiceDelete?: (choiceId: number, questionIndex: number) => void;
}

export const useQuestionsManager = ({
    questions,
    onQuestionsChange,
    onQuestionDelete,
    onChoiceDelete,

}: UseQuestionsManagerProps) => {
    const [showAddDropdown, setShowAddDropdown] = useState(false);
    const [collapsedQuestions, setCollapsedQuestions] = useState<Set<string>>(new Set());

    const deleteHistory = useDeleteHistory({ questions, onQuestionsChange });

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


    const handleRequestQuestionDeletion = (index: number, question: QuestionFormData) => {
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
    };

    const handleRequestChoiceDeletion = (questionIndex: number, choiceIndex: number, question: QuestionFormData, choice: ChoiceFormData) => {
        setConfirmationModal({
            isOpen: true,
            type: 'choice',
            title: 'Confirmer la suppression',
            message: `Êtes-vous sûr de vouloir supprimer ce choix ? `,
            onConfirm: () => {
                confirmChoiceDeletion(questionIndex, choiceIndex, question, choice);
                setConfirmationModal(prev => ({ ...prev, isOpen: false }));
            }
        });
    };

    const confirmQuestionDeletion = (index: number, question: QuestionFormData) => {
        if (question.id) {
            deleteHistory.addDeletedQuestion(question.id, index);
            if (onQuestionDelete) {
                onQuestionDelete(question.id);
            }
        }
        onQuestionsChange(questions.filter((_, i) => i !== index));
    };

    const confirmChoiceDeletion = (questionIndex: number, choiceIndex: number, _question: QuestionFormData, choice: ChoiceFormData) => {
        if (choice.id) {
            deleteHistory.addDeletedChoice(choice.id, questionIndex, choiceIndex);
            if (onChoiceDelete) {
                onChoiceDelete(choice.id, questionIndex);
            }
        }
        onQuestionsChange(questions.map((q, i) => {
            if (i === questionIndex && q.choices.length > 2) {
                const filteredChoices = q.choices.filter((_, ci) => ci !== choiceIndex);
                const reorderedChoices = filteredChoices.map((choice, index) => ({
                    ...choice,
                    order_index: index + 1
                }));
                return { ...q, choices: reorderedChoices };
            }
            return q;
        }));
    };

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            const oldIndex = questions.findIndex((_, index) => index.toString() === active.id);
            const newIndex = questions.findIndex((_, index) => index.toString() === over.id);

            const reorderedQuestions = arrayMove(questions, oldIndex, newIndex);

            const questionsWithUpdatedOrder = reorderedQuestions.map((question, index) => ({
                ...question,
                order_index: index + 1
            }));

            onQuestionsChange(questionsWithUpdatedOrder);
        }
    };

    const toggleAddDropdown = () => {
        setShowAddDropdown(!showAddDropdown);
    };

    const getQuestionTypeIcon = getIcon;

    const addQuestion = (type: QuestionType) => {
        const newQuestion = createDefaultQuestion(type, questions.length + 1);
        onQuestionsChange([...questions, newQuestion]);
        setShowAddDropdown(false);
    };

    const removeQuestion = (index: number) => {
        const question = questions[index];

        if (question.id && handleRequestQuestionDeletion) {
            handleRequestQuestionDeletion(index, question);
        } else {
            onQuestionsChange(questions.filter((_, i) => i !== index));
        }
    };

    const updateQuestion = (index: number, field: keyof QuestionFormData, value: any) => {
        onQuestionsChange(questions.map((q, i) => {
            if (i === index) {
                const updatedQuestion = { ...q, [field]: value };

                if (field === 'type' && value === 'boolean') {
                    updatedQuestion.choices = [
                        {
                            content: 'true',
                            is_correct: true,
                            order_index: 1
                        },
                        {
                            content: 'false',
                            is_correct: false,
                            order_index: 2
                        }
                    ];
                }
                else if (field === 'type' && q.type === 'boolean' && value !== 'boolean') {
                    if (value === 'multiple' || value === 'one_choice') {
                        updatedQuestion.choices = [
                            {
                                content: '',
                                is_correct: true,
                                order_index: 1
                            },
                            {
                                content: '',
                                is_correct: false,
                                order_index: 2
                            }
                        ];
                    } else if (value === 'text') {
                        updatedQuestion.choices = [];
                    }
                }

                return updatedQuestion;
            }
            return q;
        }));
    };

    const addChoice = (questionIndex: number) => {
        onQuestionsChange(questions.map((q, i) => {
            if (i === questionIndex) {
                const newChoice: ChoiceFormData = {
                    content: '',
                    is_correct: false,
                    order_index: q.choices.length + 1
                };
                return { ...q, choices: [...q.choices, newChoice] };
            }
            return q;
        }));
    };

    const removeChoice = (questionIndex: number, choiceIndex: number) => {
        const question = questions[questionIndex];
        const choice = question.choices[choiceIndex];

        if (choice.id && handleRequestChoiceDeletion) {
            handleRequestChoiceDeletion(questionIndex, choiceIndex, question, choice);
        } else {
            onQuestionsChange(questions.map((q, i) => {
                if (i === questionIndex && q.choices.length > 2) {
                    const filteredChoices = q.choices.filter((_, ci) => ci !== choiceIndex);
                    const reorderedChoices = filteredChoices.map((choice, index) => ({
                        ...choice,
                        order_index: index + 1
                    }));
                    return { ...q, choices: reorderedChoices };
                }
                return q;
            }));
        }
    };

    const updateChoice = (questionIndex: number, choiceIndex: number, field: keyof ChoiceFormData, value: any) => {
        onQuestionsChange(questions.map((q, i) => {
            if (i === questionIndex) {
                return {
                    ...q,
                    choices: q.choices.map((c, ci) => {
                        if (ci === choiceIndex) {
                            if (field === 'is_correct' && value && q.type === 'one_choice') {
                                return { ...c, [field]: value };
                            }
                            return { ...c, [field]: value };
                        } else if (field === 'is_correct' && value && q.type === 'one_choice') {
                            return { ...c, is_correct: false };
                        }
                        return c;
                    })
                };
            }
            return q;
        }));
    };

    const toggleCollapse = (index: number) => {
        const newCollapsed = new Set(collapsedQuestions);
        const questionKey = `question-${index}`;
        if (newCollapsed.has(questionKey)) {
            newCollapsed.delete(questionKey);
        } else {
            newCollapsed.add(questionKey);
        }
        setCollapsedQuestions(newCollapsed);
    };

    return {
        showAddDropdown,
        collapsedQuestions,
        handleDragEnd,
        toggleAddDropdown,
        getQuestionTypeIcon,
        addQuestion,
        removeQuestion,
        updateQuestion,
        addChoice,
        removeChoice,
        updateChoice,
        toggleCollapse,
        deleteHistory,
        confirmationModal,
        historyModalOpen,
        setHistoryModalOpen,
        setConfirmationModal
    };
};