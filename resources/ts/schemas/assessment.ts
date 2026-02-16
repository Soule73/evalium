import { z } from 'zod';

export const QuestionTypeSchema = z.enum(['multiple', 'text', 'one_choice', 'boolean']);

export const ChoiceFormDataSchema = z.object({
    id: z.number().optional(),
    content: z.string().min(1, 'Choice content is required'),
    is_correct: z.boolean(),
    order_index: z.number().int().min(0),
});

export const QuestionFormDataSchema = z
    .object({
        id: z.number().optional(),
        content: z.string().min(1, 'Question content is required'),
        type: QuestionTypeSchema,
        points: z.number().positive('Points must be positive'),
        order_index: z.number().int().min(0),
        choices: z.array(ChoiceFormDataSchema).min(0),
    })
    .refine(
        (data) => {
            if (data.type === 'multiple' || data.type === 'one_choice') {
                return data.choices.length >= 2;
            }
            return true;
        },
        {
            message: 'Choice questions must have at least 2 choices',
            path: ['choices'],
        },
    )
    .refine(
        (data) => {
            if (data.type === 'multiple') {
                const correctChoices = data.choices.filter((c) => c.is_correct);
                return correctChoices.length >= 2;
            }
            if (data.type === 'one_choice') {
                const correctChoices = data.choices.filter((c) => c.is_correct);
                return correctChoices.length === 1;
            }
            return true;
        },
        {
            message: 'Invalid number of correct choices for question type',
            path: ['choices'],
        },
    );

export const ExamFormDataSchema = z.object({
    title: z.string().min(3, 'Title must be at least 3 characters').max(255),
    description: z.string().max(1000).optional(),
    duration: z.number().int().positive('Duration must be positive'),
    is_active: z.boolean(),
    start_time: z.string().optional(),
    end_time: z.string().optional(),
    questions: z.array(QuestionFormDataSchema).min(1, 'At least one question is required'),
    deletedQuestionIds: z.array(z.number()).optional(),
    deletedChoiceIds: z.array(z.number()).optional(),
});

export const CreateExamRequestSchema = ExamFormDataSchema.omit({
    deletedQuestionIds: true,
    deletedChoiceIds: true,
});

export const UpdateExamRequestSchema = ExamFormDataSchema.partial().extend({
    deletedQuestionIds: z.array(z.number()).optional(),
    deletedChoiceIds: z.array(z.number()).optional(),
});

export type QuestionFormData = z.infer<typeof QuestionFormDataSchema>;
export type ChoiceFormData = z.infer<typeof ChoiceFormDataSchema>;
export type ExamFormData = z.infer<typeof ExamFormDataSchema>;
export type CreateExamRequest = z.infer<typeof CreateExamRequestSchema>;
export type UpdateExamRequest = z.infer<typeof UpdateExamRequestSchema>;
