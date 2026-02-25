import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useShallow } from 'zustand/react/shallow';
import { type QuestionFormData, type AssessmentType, type DeliveryMode } from '@/types';
import { useAssessmentFormStore } from '@/stores/useAssessmentFormStore';

interface AssessmentCreateData {
    title: string;
    description: string;
    duration: number;
    scheduled_date: string;
    due_date: string;
    delivery_mode: DeliveryMode;
    type: AssessmentType;
    class_subject_id: number | null;
    coefficient: number;
    is_published: boolean;
    shuffle_questions: boolean;
    release_results_after_grading: boolean;
    show_correct_answers: boolean;
    allow_late_submission: boolean;
    questions: QuestionFormData[];
}

export const useCreateAssessment = () => {
    const { questions, resetStore } = useAssessmentFormStore(
        useShallow((state) => ({
            questions: state.questions,
            resetStore: state.reset,
        })),
    );

    const { data, setData, post, processing, errors, reset, clearErrors, transform } =
        useForm<AssessmentCreateData>({
            title: '',
            description: '',
            duration: 60,
            scheduled_date: '',
            due_date: '',
            delivery_mode: 'homework',
            type: 'homework',
            class_subject_id: null,
            is_published: false,
            coefficient: 1,
            shuffle_questions: false,
            release_results_after_grading: false,
            show_correct_answers: false,
            allow_late_submission: false,
            questions: [],
        });

    useEffect(() => {
        resetStore();
    }, [resetStore]);

    const handleFieldChange = (field: string, value: string | number | boolean) => {
        setData(field as keyof AssessmentCreateData, value as never);
        if (field === 'delivery_mode') {
            setData('shuffle_questions', value === 'supervised');
            if (value !== 'supervised') {
                setData('duration', 0);
            }
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        clearErrors();

        transform((data) => ({
            ...data,
            questions,
        }));

        post(route('teacher.assessments.store'), {
            onSuccess: () => {
                reset();
                resetStore();
            },
        });
    };

    return {
        data,
        errors,
        processing,
        handleFieldChange,
        handleSubmit,
        reset,
    };
};
