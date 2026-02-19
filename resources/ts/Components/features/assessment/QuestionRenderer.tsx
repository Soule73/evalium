import React, { memo } from 'react';
import { type Question, type QuestionResult } from '@/types';
import { hasUserResponse } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AlertEntry } from '@evalium/ui';
import { Section } from '@/Components/ui';
import { QuestionReadOnlySection } from './QuestionReadOnlySection';
import {
    QuestionResultReadOnlyText,
    QuestionResultReadOnlyChoices,
} from './QuestionResultReadOnly';

interface QuestionItemProps {
    question: Question;
    questionIndex?: number;
    getQuestionResult: (question: Question) => QuestionResult;
    scores?: Record<number, number>;
    isTeacherView?: boolean;
    isEditMode?: boolean;
    renderScoreInput?: (question: Question) => React.ReactNode;
    showCorrectAnswers?: boolean;
    /** Suppresses the "no answer" warning. Use when rendering an assessment preview without student context. */
    previewMode?: boolean;
}

/**
 * Renders a single question with its result, score, feedback, and optional score input.
 * Memoized to prevent re-renders when sibling questions change.
 */
const QuestionItem: React.FC<QuestionItemProps> = memo(
    ({
        question,
        questionIndex = 0,
        getQuestionResult,
        scores,
        isTeacherView = true,
        renderScoreInput,
        showCorrectAnswers = false,
        isEditMode = false,
        previewMode = false,
    }) => {
        const { t } = useTranslations();
        const result = getQuestionResult(question);
        const hasResponse = hasUserResponse(result);
        const questionScore = scores?.[question.id] ?? result?.score ?? null;

        return (
            <div className="border border-gray-200 rounded-lg p-6">
                <QuestionReadOnlySection
                    isCorrect={result.isCorrect}
                    question={question}
                    score={questionScore}
                    questionIndex={questionIndex}
                >
                    {question.type === 'text' && result.userText && (
                        <QuestionResultReadOnlyText
                            userText={result.userText}
                            label={
                                isTeacherView
                                    ? t('components.question_renderer.student_answer_label')
                                    : t('components.question_renderer.your_answer_label')
                            }
                        />
                    )}

                    {(question.type === 'one_choice' ||
                        question.type === 'multiple' ||
                        question.type === 'boolean') && (
                            <QuestionResultReadOnlyChoices
                                choices={question.choices ?? []}
                                userChoices={result.userChoices}
                                type={question.type}
                                isTeacherView={isTeacherView}
                                showCorrectAnswers={showCorrectAnswers}
                            />
                        )}

                    {!hasResponse && !previewMode && (
                        <AlertEntry
                            title={t('components.question_renderer.no_answer')}
                            type="warning"
                            className="mt-2"
                        >
                            <p className="text-sm">
                                {isTeacherView
                                    ? t('components.question_renderer.no_answer_student')
                                    : t('components.question_renderer.no_answer_yours')}
                            </p>
                        </AlertEntry>
                    )}
                </QuestionReadOnlySection>

                {result.feedback && !isEditMode && (
                    <AlertEntry title={result.feedback} type="info" className="mt-2" />
                )}

                {renderScoreInput && isEditMode && renderScoreInput(question)}
            </div>
        );
    },
);

QuestionItem.displayName = 'QuestionItem';

interface QuestionListProps {
    questions: Question[];
    getQuestionResult: (question: Question) => QuestionResult;
    scores?: Record<number, number>;
    isTeacherView?: boolean;
    isEditMode?: boolean;
    renderScoreInput?: (question: Question) => React.ReactNode;
    showCorrectAnswers?: boolean;
    /** Suppresses the "no answer" warning. Use when rendering an assessment preview without student context. */
    previewMode?: boolean;
    /** When provided, wraps the list in a Section and adds dividers between items. */
    title?: string;
}

/**
 * Unified question list component used across Review, Grade, Admin, and Student pages.
 *
 * Provides two rendering modes:
 * - With `title`: wraps in a Section with dividers between items (teacher/admin context).
 * - Without `title`: renders a plain spaced list (student results context).
 */
const QuestionList: React.FC<QuestionListProps> = ({
    questions,
    getQuestionResult,
    scores,
    isTeacherView = true,
    isEditMode = false,
    renderScoreInput,
    showCorrectAnswers = false,
    previewMode = false,
    title,
}) => {
    if (!questions.length) return null;

    const withSection = Boolean(title);

    const content = (
        <div className="space-y-6">
            {questions.map((question, index) => (
                <div
                    key={question.id}
                    className={withSection ? 'pb-6 border-b border-gray-200 last:border-0' : undefined}
                >
                    <QuestionItem
                        question={question}
                        questionIndex={index}
                        getQuestionResult={getQuestionResult}
                        scores={scores}
                        isTeacherView={isTeacherView}
                        renderScoreInput={renderScoreInput}
                        isEditMode={isEditMode}
                        showCorrectAnswers={showCorrectAnswers}
                        previewMode={previewMode}
                    />
                </div>
            ))}
        </div>
    );

    if (withSection) {
        return <Section title={title!}>{content}</Section>;
    }

    return content;
};

export { QuestionItem, QuestionList };
