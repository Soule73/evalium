import type React from 'react';
import type { Question, QuestionResult, QuestionType } from '@/types';
import { ChoiceRenderer } from './ChoiceRenderer';
import { TextRenderer } from './TextRenderer';
import { FileRenderer } from './FileRenderer';

export interface QuestionRendererProps {
    question: Question;
    result: QuestionResult;
}

/**
 * Registry mapping each QuestionType to its Strategy renderer.
 * To add a new question type, create a new renderer file and add it here.
 */
export const QuestionRenderers: Record<QuestionType, React.FC<QuestionRendererProps>> = {
    multiple: ChoiceRenderer,
    one_choice: ChoiceRenderer,
    boolean: ChoiceRenderer,
    text: TextRenderer,
    file: FileRenderer,
};
