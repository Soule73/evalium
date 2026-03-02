import { describe, it, expect } from 'vitest';
import { QUESTION_TYPE_CONFIG, getQuestionTypeIcon } from './questionTypes';

describe('questionTypes', () => {
    describe('QUESTION_TYPE_CONFIG', () => {
        it('should have config for all question types', () => {
            expect(QUESTION_TYPE_CONFIG).toHaveProperty('multiple');
            expect(QUESTION_TYPE_CONFIG).toHaveProperty('one_choice');
            expect(QUESTION_TYPE_CONFIG).toHaveProperty('boolean');
            expect(QUESTION_TYPE_CONFIG).toHaveProperty('text');
        });

        it('should have correct structure for multiple choice', () => {
            const config = QUESTION_TYPE_CONFIG.multiple;
            expect(config).toHaveProperty('icon');
            expect(config).toHaveProperty('bgColor', 'bg-blue-100');
            expect(config).toHaveProperty('textColor', 'text-blue-600');
        });

        it('should have correct structure for one_choice', () => {
            const config = QUESTION_TYPE_CONFIG.one_choice;
            expect(config).toHaveProperty('icon');
            expect(config).toHaveProperty('bgColor', 'bg-green-100');
            expect(config).toHaveProperty('textColor', 'text-green-600');
        });

        it('should have correct structure for boolean', () => {
            const config = QUESTION_TYPE_CONFIG.boolean;
            expect(config).toHaveProperty('icon');
            expect(config).toHaveProperty('bgColor', 'bg-purple-100');
            expect(config).toHaveProperty('textColor', 'text-purple-600');
        });

        it('should have correct structure for text', () => {
            const config = QUESTION_TYPE_CONFIG.text;
            expect(config).toHaveProperty('icon');
            expect(config).toHaveProperty('bgColor', 'bg-yellow-100');
            expect(config).toHaveProperty('textColor', 'text-yellow-600');
        });
    });

    describe('getQuestionTypeIcon', () => {
        it('should return config for valid question type', () => {
            const config = getQuestionTypeIcon('multiple');
            expect(config).toBeDefined();
            expect(config).toHaveProperty('icon');
            expect(config).toHaveProperty('bgColor');
            expect(config).toHaveProperty('textColor');
        });

        it('should return null for invalid question type', () => {
            const config = getQuestionTypeIcon('invalid_type');
            expect(config).toBeNull();
        });

        it('should return correct config for each type', () => {
            expect(getQuestionTypeIcon('multiple')).toBe(QUESTION_TYPE_CONFIG.multiple);
            expect(getQuestionTypeIcon('one_choice')).toBe(QUESTION_TYPE_CONFIG.one_choice);
            expect(getQuestionTypeIcon('boolean')).toBe(QUESTION_TYPE_CONFIG.boolean);
            expect(getQuestionTypeIcon('text')).toBe(QUESTION_TYPE_CONFIG.text);
        });

        it('should handle empty string', () => {
            const config = getQuestionTypeIcon('');
            expect(config).toBeNull();
        });
    });
});
