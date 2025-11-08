import { useState, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { QuestionFormData } from '@/types';

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
    const [questions, setQuestions] = useState<QuestionFormData[]>([]);

    const { data, setData, processing, errors, reset } = useForm<ExamCreateData>({
        title: '',
        description: '',
        duration: 60,
        start_time: '',
        end_time: '',
        is_active: true,
        questions: []
    });

    // Log des erreurs pour débogage
    useEffect(() => {
        if (Object.keys(errors).length > 0) {
            console.log('Erreurs de validation détectées:', errors);
        }
    }, [errors]);

    const handleQuestionsChange = (newQuestions: QuestionFormData[]) => {
        setQuestions(newQuestions);
        // Ne pas appeler setData ici pour éviter les re-renders
        // setData('questions', newQuestions);
    };

    const handleFieldChange = (field: string, value: string | number | boolean) => {
        setData(field as keyof ExamCreateData, value);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const validationErrors: Record<string, string> = {};

        // Validation du titre
        if (!data.title.trim()) {
            validationErrors.title = 'Le titre est requis';
        }

        // Validation de la durée
        if (!data.duration || data.duration < 1) {
            validationErrors.duration = 'La durée doit être d\'au moins 1 minute';
        }

        // Validation des questions
        if (questions.length === 0) {
            alert('Vous devez ajouter au moins une question');
            return;
        }

        for (let i = 0; i < questions.length; i++) {
            const question = questions[i];
            if (!question.content.trim()) {
                alert(`La question ${i + 1} doit avoir un contenu`);
                return;
            }
        }

        // Si des erreurs, ne pas soumettre
        if (Object.keys(validationErrors).length > 0) {
            console.error('Erreurs de validation:', validationErrors);
            return;
        }

        // Préparer les données complètes à envoyer
        const submitData = {
            title: data.title,
            description: data.description,
            duration: data.duration,
            start_time: data.start_time,
            end_time: data.end_time,
            is_active: data.is_active,
            questions
        };

        console.log('Données soumises:', submitData);

        router.post(route('exams.store'), submitData as any, {
            onSuccess: () => {
                reset();
                setQuestions([]);
            },
            onError: (errors) => {
                console.error('Erreurs de soumission:', errors);
            }
        });
    };

    return {
        data,
        errors,
        processing,
        questions,
        handleQuestionsChange,
        handleFieldChange,
        handleSubmit,
        reset
    };
};