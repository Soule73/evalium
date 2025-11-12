import { useEffect, useCallback } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useShallow } from 'zustand/react/shallow';
import { QuestionFormData, Exam } from '@/types';
import { useExamFormStore } from '@/stores';

interface ExamEditData {
    title: string;
    description: string;
    duration: number;
    start_time: string;
    end_time: string;
    is_active: boolean;
    questions: QuestionFormData[];
    deletedQuestionIds: number[];
    deletedChoiceIds: number[];
}

interface UseEditExamReturn {
    data: ExamEditData;
    errors: Record<string, string>;
    processing: boolean;
    handleFieldChange: (field: string, value: string | number | boolean) => void;
    handleSubmit: (e: React.FormEvent) => void;
}

export const useEditExam = (exam: Exam): UseEditExamReturn => {
    const {
        questions,
        setQuestions,
        deletedQuestionIds,
        deletedChoiceIds,
        clearDeletedHistory,
        resetStore,
    } = useExamFormStore(useShallow((state) => ({
        questions: state.questions,
        setQuestions: state.setQuestions,
        deletedQuestionIds: state.deletedQuestionIds,
        deletedChoiceIds: state.deletedChoiceIds,
        clearDeletedHistory: state.clearDeletedHistory,
        resetStore: state.reset,
    })));

    const { data, setData, put, processing, errors, transform, clearErrors } = useForm<ExamEditData>({
        title: exam.title || '',
        description: exam.description || '',
        duration: exam.duration || 60,
        start_time: exam.start_time ? exam.start_time.slice(0, 16) : '',
        end_time: exam.end_time ? exam.end_time.slice(0, 16) : '',
        is_active: exam.is_active ?? true,
        questions: [],
        deletedQuestionIds: [],
        deletedChoiceIds: []
    });

    useEffect(() => {
        if (exam.questions && questions.length === 0) {
            const formattedQuestions: QuestionFormData[] = exam.questions.map((q, index) => ({
                id: q.id,
                content: q.content,
                type: q.type,
                points: q.points,
                order_index: q.order_index || index,
                choices: q.choices?.map((c, choiceIndex) => {
                    let content = c.content;
                    if (q.type === 'boolean') {
                        if (content !== 'true' && content !== 'false') {
                            content = choiceIndex === 0 ? 'true' : 'false';
                        }
                    }
                    return {
                        id: c.id,
                        content,
                        is_correct: c.is_correct,
                        order_index: c.order_index || choiceIndex
                    };
                }) || []
            }));
            setQuestions(formattedQuestions);
            setData('questions', formattedQuestions);
        }

        return () => {
            resetStore();
        };
    }, []);

    const handleFieldChange = useCallback((field: string, value: string | number | boolean) => {
        setData(field as keyof ExamEditData, value);
    }, [setData]);

    const handleSubmit = useCallback((e: React.FormEvent) => {
        e.preventDefault();

        clearErrors();

        transform((data) => ({
            ...data,
            questions,
            deletedQuestionIds,
            deletedChoiceIds
        }));

        put(route('exams.update', exam.id), {
            onSuccess: () => {
                clearDeletedHistory();
            }
        });
    }, [questions, deletedQuestionIds, deletedChoiceIds, exam.id, clearDeletedHistory, clearErrors, transform, put]);

    return {
        data,
        errors,
        processing,
        handleFieldChange,
        handleSubmit,
    };
};