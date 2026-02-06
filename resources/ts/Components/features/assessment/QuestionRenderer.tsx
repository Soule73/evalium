import React from 'react';
import { Question, QuestionResult, AssessmentAssignment } from '@/types';
import { hasUserResponse } from '@/utils';
import { trans } from '@/utils';
import { AlertEntry } from '@examena/ui';
import { QuestionReadOnlySection } from './QuestionReadOnlySection';
import { QuestionResultReadOnlyText, QuestionResultReadOnlyChoices } from './QuestionResultReadOnly';

interface QuestionRendererProps {
    questions: Question[];
    getQuestionResult: (question: Question) => QuestionResult;
    scores?: Record<number, number>;
    isTeacherView?: boolean;
    renderScoreInput?: (question: Question) => React.ReactNode;
    showCorrectAnswers?: boolean;
    assignment?: AssessmentAssignment;
}

/**
 * Composant commun pour le rendu des questions avec leurs réponses
 */
const QuestionRenderer: React.FC<QuestionRendererProps> = ({
    questions,
    getQuestionResult,
    scores,
    isTeacherView = true,
    renderScoreInput,
    showCorrectAnswers = false,
}) => {
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
                                    label={isTeacherView ? trans('components.question_renderer.student_answer_label') : trans('components.question_renderer.your_answer_label')}
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
                                <AlertEntry title={trans('components.question_renderer.no_answer')} type="warning">
                                    <p className="text-sm">
                                        {isTeacherView
                                            ? trans('components.question_renderer.no_answer_student')
                                            : trans('components.question_renderer.no_answer_yours')
                                        }
                                    </p>
                                </AlertEntry>
                            )}
                        </QuestionReadOnlySection>

                        {/* Affichage du feedback du professeur */}
                        {result.feedback && (
                            <AlertEntry title={result.feedback} type="info" />
                        )}

                        {/* Interface de notation (seulement en mode correction) */}
                        {renderScoreInput && renderScoreInput(question)}
                    </div>
                );
            })}
        </div>
    );
};

export { QuestionRenderer };