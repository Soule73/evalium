import type { QuestionFormData, QuestionType, ChoiceFormData } from '@/types';

let questionIdCounter = 0;

export function createDefaultQuestion(type: QuestionType, orderIndex: number): QuestionFormData {
    const question: QuestionFormData = {
        id: -(Date.now() + ++questionIdCounter),
        content: '',
        type,
        points: 1,
        order_index: orderIndex,
        choices: [],
    };

    if (type === 'multiple' || type === 'one_choice') {
        question.choices = createDefaultChoices();
    } else if (type === 'boolean') {
        question.choices = createBooleanChoices();
    }

    return question;
}

export function createDefaultChoices(): ChoiceFormData[] {
    return [
        {
            content: '',
            is_correct: true,
            order_index: 1,
        },
        {
            content: '',
            is_correct: false,
            order_index: 2,
        },
    ];
}

export function createBooleanChoices(): ChoiceFormData[] {
    return [
        {
            content: 'true',
            is_correct: true,
            order_index: 1,
        },
        {
            content: 'false',
            is_correct: false,
            order_index: 2,
        },
    ];
}

export function createChoice(orderIndex: number, isCorrect: boolean = false): ChoiceFormData {
    return {
        content: '',
        is_correct: isCorrect,
        order_index: orderIndex,
    };
}
