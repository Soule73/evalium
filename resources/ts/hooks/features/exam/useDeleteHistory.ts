import { useState, useCallback } from 'react';
import { QuestionFormData, ChoiceFormData } from '@/types';

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

interface UseDeleteHistoryProps {
    questions: QuestionFormData[];
    onQuestionsChange: (questions: QuestionFormData[]) => void;
}

export const useDeleteHistory = ({ questions, onQuestionsChange }: UseDeleteHistoryProps) => {
    const [deletedQuestions, setDeletedQuestions] = useState<DeletedQuestion[]>([]);
    const [deletedChoices, setDeletedChoices] = useState<DeletedChoice[]>([]);

    const addDeletedQuestion = useCallback((questionId: number, index: number) => {
        const question = questions[index];
        if (question) {
            const deletedQuestion: DeletedQuestion = {
                id: questionId,
                question: { ...question },
                deletedAt: new Date(),
                index
            };
            setDeletedQuestions(prev => [...prev, deletedQuestion]);
        }
    }, [questions]);

    const addDeletedChoice = useCallback((choiceId: number, questionIndex: number, choiceIndex: number) => {
        const question = questions[questionIndex];
        const choice = question?.choices[choiceIndex];
        if (choice) {
            const deletedChoice: DeletedChoice = {
                id: choiceId,
                choice: { ...choice },
                questionId: question.id || 0,
                questionIndex,
                choiceIndex,
                deletedAt: new Date()
            };
            setDeletedChoices(prev => [...prev, deletedChoice]);
        }
    }, [questions]);

    const restoreQuestion = useCallback((deletedQuestion: DeletedQuestion) => {

        const newQuestions = [...questions];
        const insertIndex = Math.min(deletedQuestion.index, newQuestions.length);
        newQuestions.splice(insertIndex, 0, deletedQuestion.question);

        const updatedQuestions = newQuestions.map((q, index) => ({
            ...q,
            order_index: index + 1
        }));

        onQuestionsChange(updatedQuestions);

        setDeletedQuestions(prev => prev.filter(dq => dq.id !== deletedQuestion.id));
    }, [questions, onQuestionsChange]);

    const restoreChoice = useCallback((deletedChoice: DeletedChoice) => {
        const questionIndex = questions.findIndex(q => q.id === deletedChoice.questionId);
        if (questionIndex === -1) {
            return;
        }

        const newQuestions = [...questions];
        const question = newQuestions[questionIndex];
        const newChoices = [...question.choices];

        const insertIndex = Math.min(deletedChoice.choiceIndex, newChoices.length);
        newChoices.splice(insertIndex, 0, deletedChoice.choice);

        const updatedChoices = newChoices.map((c, index) => ({
            ...c,
            order_index: index + 1
        }));

        newQuestions[questionIndex] = {
            ...question,
            choices: updatedChoices
        };

        onQuestionsChange(newQuestions);

        setDeletedChoices(prev => prev.filter(dc => dc.id !== deletedChoice.id));
    }, [questions, onQuestionsChange]);

    const clearHistory = useCallback(() => {
        setDeletedQuestions([]);
        setDeletedChoices([]);
    }, []);

    const getDeletedQuestionsCount = useCallback(() => {
        return deletedQuestions.length;
    }, [deletedQuestions]);

    const getDeletedChoicesCount = useCallback(() => {
        return deletedChoices.length;
    }, [deletedChoices]);

    const hasDeletedItems = useCallback(() => {
        return deletedQuestions.length > 0 || deletedChoices.length > 0;
    }, [deletedQuestions, deletedChoices]);

    return {
        deletedQuestions,
        deletedChoices,
        addDeletedQuestion,
        addDeletedChoice,
        restoreQuestion,
        restoreChoice,
        clearHistory,
        getDeletedQuestionsCount,
        getDeletedChoicesCount,
        hasDeletedItems
    };
};