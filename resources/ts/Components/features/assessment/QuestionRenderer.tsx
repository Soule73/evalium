import React from 'react';
import { type Question, type QuestionResult, type AssessmentAssignment } from '@/types';
import { hasUserResponse } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AlertEntry } from '@evalium/ui';
import { QuestionReadOnlySection } from './QuestionReadOnlySection';
import { QuestionResultReadOnlyText, QuestionResultReadOnlyChoices } from './QuestionResultReadOnly';

interface QuestionRendererProps {
    questions: Question[];
    getQuestionResult: (question: Question) => QuestionResult;
    scores?: Record<number, number>;
    isTeacherView?: boolean;
    isEditMode?: boolean;
    renderScoreInput?: (question: Question) => React.ReactNode;
    showCorrectAnswers?: boolean;
    assignment?: AssessmentAssignment;
}


const QuestionRenderer: React.FC<QuestionRendererProps> = ({
    questions,
    getQuestionResult,
    scores,
    isTeacherView = true,
    renderScoreInput,
    showCorrectAnswers = false,
    isEditMode = false,
}) => {
    const { t } = useTranslations();

    return (
        <div className="space-y-6">
            {questions.map((question, index) => {
                const result = getQuestionResult(question);
                const hasResponse = hasUserResponse(result);
                const questionScore = scores?.[question.id] ?? result?.score ?? null;

                return (
                    <div key={question.id} className="border border-gray-200 rounded-lg p-6">
                        <QuestionReadOnlySection
                            isCorrect={result.isCorrect}
                            question={question}
                            score={questionScore}
                            questionIndex={index}
                        >
                            {question.type === 'text' && (
                                <QuestionResultReadOnlyText
                                    userText={result.userText}
                                    label={isTeacherView ? t('components.question_renderer.student_answer_label') : t('components.question_renderer.your_answer_label')}
                                />
                            )}

                            {(question.type === 'one_choice' || question.type === 'multiple' || question.type === 'boolean') && (
                                <QuestionResultReadOnlyChoices
                                    choices={question.choices ?? []}
                                    userChoices={result.userChoices}
                                    type={question.type}
                                    isTeacherView={isTeacherView}
                                    showCorrectAnswers={showCorrectAnswers}
                                />
                            )}

                            {!hasResponse && (
                                <AlertEntry title={t('components.question_renderer.no_answer')} type="warning">
                                    <p className="text-sm">
                                        {isTeacherView
                                            ? t('components.question_renderer.no_answer_student')
                                            : t('components.question_renderer.no_answer_yours')
                                        }
                                    </p>
                                </AlertEntry>
                            )}
                        </QuestionReadOnlySection>

                        {result.feedback && !isEditMode && (
                            <AlertEntry title={result.feedback} type="info" />
                        )}

                        {renderScoreInput && isEditMode && renderScoreInput(question)}
                    </div>
                );
            })}
        </div>
    );
};

export { QuestionRenderer };