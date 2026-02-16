import { describe, it, expect } from 'vitest';
import {
    createDefaultQuestion,
    createDefaultChoices,
    createBooleanChoices,
    createChoice,
} from './questionFactory';

describe('questionFactory', () => {
    describe('createDefaultQuestion', () => {
        it('should create a multiple choice question with default choices', () => {
            const question = createDefaultQuestion('multiple', 1);

            expect(question).toHaveProperty('id');
            expect(question.id).toBeLessThan(0);
            expect(question.content).toBe('');
            expect(question.type).toBe('multiple');
            expect(question.points).toBe(1);
            expect(question.order_index).toBe(1);
            expect(question.choices).toHaveLength(2);
            expect(question.choices![0].is_correct).toBe(true);
            expect(question.choices![1].is_correct).toBe(false);
        });

        it('should create a one_choice question with default choices', () => {
            const question = createDefaultQuestion('one_choice', 2);

            expect(question.type).toBe('one_choice');
            expect(question.order_index).toBe(2);
            expect(question.choices).toHaveLength(2);
        });

        it('should create a boolean question with boolean choices', () => {
            const question = createDefaultQuestion('boolean', 3);

            expect(question.type).toBe('boolean');
            expect(question.order_index).toBe(3);
            expect(question.choices).toHaveLength(2);
            expect(question.choices![0].content).toBe('true');
            expect(question.choices![1].content).toBe('false');
            expect(question.choices![0].is_correct).toBe(true);
            expect(question.choices![1].is_correct).toBe(false);
        });

        it('should create a text question without choices', () => {
            const question = createDefaultQuestion('text', 4);

            expect(question.type).toBe('text');
            expect(question.order_index).toBe(4);
            expect(question.choices).toHaveLength(0);
        });

        it('should generate unique negative IDs', () => {
            const question1 = createDefaultQuestion('multiple', 1);
            const question2 = createDefaultQuestion('multiple', 2);

            expect(question1.id).not.toBe(question2.id);
            expect(question1.id).toBeLessThan(0);
            expect(question2.id).toBeLessThan(0);
        });
    });

    describe('createDefaultChoices', () => {
        it('should create two default choices', () => {
            const choices = createDefaultChoices();

            expect(choices).toHaveLength(2);
        });

        it('should create first choice as correct', () => {
            const choices = createDefaultChoices();

            expect(choices[0].is_correct).toBe(true);
            expect(choices[0].order_index).toBe(1);
            expect(choices[0].content).toBe('');
        });

        it('should create second choice as incorrect', () => {
            const choices = createDefaultChoices();

            expect(choices[1].is_correct).toBe(false);
            expect(choices[1].order_index).toBe(2);
            expect(choices[1].content).toBe('');
        });
    });

    describe('createBooleanChoices', () => {
        it('should create true and false choices', () => {
            const choices = createBooleanChoices();

            expect(choices).toHaveLength(2);
            expect(choices[0].content).toBe('true');
            expect(choices[1].content).toBe('false');
        });

        it('should set true as correct by default', () => {
            const choices = createBooleanChoices();

            expect(choices[0].is_correct).toBe(true);
            expect(choices[1].is_correct).toBe(false);
        });

        it('should set correct order indices', () => {
            const choices = createBooleanChoices();

            expect(choices[0].order_index).toBe(1);
            expect(choices[1].order_index).toBe(2);
        });
    });

    describe('createChoice', () => {
        it('should create a choice with given parameters', () => {
            const choice = createChoice(3, false);

            expect(choice.content).toBe('');
            expect(choice.order_index).toBe(3);
            expect(choice.is_correct).toBe(false);
        });

        it('should create a correct choice when specified', () => {
            const choice = createChoice(1, true);

            expect(choice.is_correct).toBe(true);
        });

        it('should default isCorrect to false when not specified', () => {
            const choice = createChoice(2);

            expect(choice.is_correct).toBe(false);
        });
    });
});
