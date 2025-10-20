import React from 'react';
import { Question } from '@/types';
import QuestionReadOnlySection from '@/Components/exam/QuestionReadOnlySection';
import { QuestionResultReadOnlyChoices, QuestionResultReadOnlyText } from '@/Components/exam/QuestionResultReadOnly';
import AlertEntry from '@/Components/AlertEntry';
import { hasUserResponse } from '@/utils/examUtils';

interface QuestionRendererProps {
    questions: Question[];
    getQuestionResult: (question: Question) => any;
    scores?: Record<number, number>;
    isTeacherView?: boolean;
    renderScoreInput?: (question: Question) => React.ReactNode;
    showCorrectAnswers?: boolean;
    assignment?: any;
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
                const questionScore = scores ? scores[question.id] : result.score;

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
                                    label={isTeacherView ? "Réponse de l'étudiant" : "Votre réponse"}
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
                                <AlertEntry title="Aucune réponse fournie" type="warning">
                                    <p className="text-sm">
                                        {isTeacherView
                                            ? "L'étudiant n'a pas fourni de réponse pour cette question."
                                            : "Vous n'avez pas fourni de réponse pour cette question."
                                        }
                                    </p>
                                </AlertEntry>
                            )}
                        </QuestionReadOnlySection>

                        {/* Affichage du feedback du professeur */}
                        {result.feedback && (
                            <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 className="text-sm font-medium text-blue-800 mb-2">
                                    Commentaire du professeur :
                                </h4>
                                <p className="text-sm text-blue-700">{result.feedback}</p>
                            </div>
                        )}

                        {/* Interface de notation (seulement en mode correction) */}
                        {renderScoreInput && renderScoreInput(question)}
                    </div>
                );
            })}
        </div>
    );
};

export default QuestionRenderer;