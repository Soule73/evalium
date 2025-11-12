import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useShallow } from 'zustand/react/shallow';
import { QuestionFormData } from '@/types';
import { useExamFormStore } from '@/stores';

interface ExamCreateData {
    title: string;
    description: string;
    duration: number;
    start_time: string;
    end_time: string;
    is_active: boolean;
    questions: QuestionFormData[];
}

export const useCreateExam = () => {
    const { questions, resetStore } = useExamFormStore(useShallow((state) => ({
        questions: state.questions,
        resetStore: state.reset,
    })));

    const { data, setData, post, processing, errors, reset, transform, clearErrors } = useForm<ExamCreateData>({
        title: '',
        description: '',
        duration: 60,
        start_time: '',
        end_time: '',
        is_active: true,
        questions: []
    });

    useEffect(() => {
        resetStore();
    }, [resetStore]);

    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            console.log('Erreurs de validation détectées:', errors);
        }
    }, [errors]);

    const handleFieldChange = (field: string, value: string | number | boolean) => {
        setData(field as keyof ExamCreateData, value);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        clearErrors();

        console.log('Données soumises:', { ...data, questions });

        transform((data) => ({
            ...data,
            questions
        }));

        post(route('exams.store'), {
            onSuccess: () => {
                reset();
                resetStore();
            }
        });
    };

    return {
        data,
        errors,
        processing,
        handleFieldChange,
        handleSubmit,
        reset
    };
};