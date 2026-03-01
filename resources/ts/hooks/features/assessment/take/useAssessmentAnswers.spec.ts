import { describe, it, expect } from 'vitest';
import { buildInitialAnswers } from '@/utils/assessment/take/answerUtils';
import type { Question } from '@/types';
import type { Answer } from '@/types';

const makeQuestion = (
    overrides: Partial<Question> & { id: number; type: Question['type'] },
): Question => ({
    assessment_id: 1,
    content: 'Question',
    points: 5,
    order_index: 1,
    created_at: '',
    updated_at: '',
    ...overrides,
});

const makeAnswer = (overrides: Partial<Answer> & { question_id: number }): Answer => ({
    id: 1,
    assessment_assignment_id: 1,
    created_at: '',
    updated_at: '',
    ...overrides,
});

describe('buildInitialAnswers', () => {
    it('should restore multiple choice answers as number array', () => {
        const questions = [makeQuestion({ id: 10, type: 'multiple' })];
        const userAnswers = [
            makeAnswer({ id: 1, question_id: 10, choice_id: 100 }),
            makeAnswer({ id: 2, question_id: 10, choice_id: 101 }),
            makeAnswer({ id: 3, question_id: 10, choice_id: 102 }),
        ];

        const result = buildInitialAnswers(questions, userAnswers);

        expect(result[10]).toEqual([100, 101, 102]);
    });

    it('should restore one_choice answer as single number', () => {
        const questions = [makeQuestion({ id: 20, type: 'one_choice' })];
        const userAnswers = [makeAnswer({ id: 1, question_id: 20, choice_id: 200 })];

        const result = buildInitialAnswers(questions, userAnswers);

        expect(result[20]).toBe(200);
    });

    it('should restore boolean answer as single number', () => {
        const questions = [makeQuestion({ id: 30, type: 'boolean' })];
        const userAnswers = [makeAnswer({ id: 1, question_id: 30, choice_id: 300 })];

        const result = buildInitialAnswers(questions, userAnswers);

        expect(result[30]).toBe(300);
    });

    it('should restore text answer as string', () => {
        const questions = [makeQuestion({ id: 40, type: 'text' })];
        const userAnswers = [makeAnswer({ id: 1, question_id: 40, answer_text: 'My response' })];

        const result = buildInitialAnswers(questions, userAnswers);

        expect(result[40]).toBe('My response');
    });

    it('should handle mixed question types', () => {
        const questions = [
            makeQuestion({ id: 1, type: 'multiple' }),
            makeQuestion({ id: 2, type: 'one_choice' }),
            makeQuestion({ id: 3, type: 'boolean' }),
            makeQuestion({ id: 4, type: 'text' }),
        ];
        const userAnswers = [
            makeAnswer({ id: 1, question_id: 1, choice_id: 10 }),
            makeAnswer({ id: 2, question_id: 1, choice_id: 11 }),
            makeAnswer({ id: 3, question_id: 2, choice_id: 20 }),
            makeAnswer({ id: 4, question_id: 3, choice_id: 30 }),
            makeAnswer({ id: 5, question_id: 4, answer_text: 'Essay' }),
        ];

        const result = buildInitialAnswers(questions, userAnswers);

        expect(result[1]).toEqual([10, 11]);
        expect(result[2]).toBe(20);
        expect(result[3]).toBe(30);
        expect(result[4]).toBe('Essay');
    });

    it('should return empty object when no userAnswers', () => {
        const questions = [makeQuestion({ id: 1, type: 'text' })];

        const result = buildInitialAnswers(questions, []);

        expect(result).toEqual({});
    });

    it('should skip answers without matching question', () => {
        const questions = [makeQuestion({ id: 1, type: 'text' })];
        const userAnswers = [makeAnswer({ id: 1, question_id: 999, answer_text: 'orphan' })];

        const result = buildInitialAnswers(questions, userAnswers);

        expect(result).toEqual({});
    });

    it('should skip answers without question_id', () => {
        const questions = [makeQuestion({ id: 1, type: 'text' })];
        const userAnswers = [makeAnswer({ id: 1, question_id: 0, answer_text: 'no id' })];

        const result = buildInitialAnswers(questions, userAnswers);

        expect(result).toEqual({});
    });
});
