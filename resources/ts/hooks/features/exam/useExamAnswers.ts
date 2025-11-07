import { useState, useEffect } from 'react';
import { Answer, Question, BackendAnswerData } from '@/types';

interface UseExamAnswersOptions {
    questions: Question[];
    userAnswers: Answer[] | Record<string, BackendAnswerData>;
}

export function useExamAnswers({ questions, userAnswers }: UseExamAnswersOptions) {
    // État local pour les réponses
    const [answers, setAnswers] = useState<Record<number, string | number | number[]>>({});
    const [isInitialized, setIsInitialized] = useState(false);

    // Initialiser les réponses UNE SEULE FOIS
    useEffect(() => {
        if (isInitialized) return; // Éviter la réinitialisation

        const initialAnswers: Record<number, string | number | number[]> = {};

        // Traiter les données selon le format reçu du backend
        if (typeof userAnswers === 'object' && !Array.isArray(userAnswers)) {
            // Format groupé par question_id venant de getUserAnswers()
            Object.entries(userAnswers).forEach(([questionIdStr, answerData]: [string, BackendAnswerData]) => {
                const questionId = parseInt(questionIdStr);

                if (answerData.type === 'multiple' && answerData.choices) {
                    // Question multiple : extraire les choice_id
                    const choiceIds = answerData.choices.map((choice: any) => choice.choice_id);
                    initialAnswers[questionId] = choiceIds;
                } else if (answerData.type === 'single') {
                    // Question simple
                    if (answerData.answer_text !== null && answerData.answer_text !== undefined) {
                        initialAnswers[questionId] = answerData.answer_text;
                    } else if (answerData.choice_id !== null && answerData.choice_id !== undefined) {
                        initialAnswers[questionId] = answerData.choice_id;
                    }
                }
            });
        } else if (Array.isArray(userAnswers)) {
            // Format tableau classique (si jamais utilisé)
            const answersByQuestion = userAnswers.reduce((acc: Record<number, Answer[]>, answer: Answer) => {
                if (!acc[answer.question_id]) {
                    acc[answer.question_id] = [];
                }
                acc[answer.question_id].push(answer);
                return acc;
            }, {} as Record<number, Answer[]>);

            questions.forEach(question => {
                const questionAnswers = answersByQuestion[question.id] || [];

                if (question.type === 'multiple') {
                    const choiceIds = questionAnswers
                        .filter((answer: Answer) => answer.choice_id)
                        .map((answer: Answer) => answer.choice_id!);
                    initialAnswers[question.id] = choiceIds;
                } else if (question.type === 'text') {
                    const textAnswer = questionAnswers.find((answer: Answer) => answer.answer_text);
                    initialAnswers[question.id] = textAnswer?.answer_text || '';
                } else {
                    const choiceAnswer = questionAnswers.find((answer: Answer) => answer.choice_id);
                    if (choiceAnswer?.choice_id) {
                        initialAnswers[question.id] = choiceAnswer.choice_id;
                    }
                }
            });
        }

        setAnswers(initialAnswers);
        setIsInitialized(true);
    }, [userAnswers, questions, isInitialized]);

    // Fonction pour mettre à jour une réponse
    const updateAnswer = (questionId: number, value: string | number | number[]) => {
        console.log('🔄 UPDATE ANSWER:', { questionId, value, type: typeof value });
        setAnswers(prev => {
            const newAnswers = { ...prev, [questionId]: value };
            console.log('📝 AFTER UPDATE:', newAnswers);
            return newAnswers;
        });
    };

    return {
        answers,
        updateAnswer,
        setAnswers
    };
}