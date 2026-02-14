import React from 'react';
import { type Choice, type Question } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useQuestionTypeUtils } from '@/hooks/shared/useQuestionTypeUtils';
import { useChoiceUtils } from '@/hooks/shared/useChoiceUtils';
import { Checkbox } from '@examena/ui';
import { MarkdownRenderer, MarkdownEditor } from '@examena/ui';
import { Section } from '@examena/ui';
import {
    getTypeColor,
    getBooleanDisplay,
    getBooleanBadgeClass,
    questionIndexLabel,
} from '@/utils/assessment/components';

type AnswerValue = string | number | number[];

interface TakeQuestionProps {
    question: Question;
    answers: Record<number, AnswerValue>;
    onAnswerChange: (questionId: number, value: AnswerValue) => void;
}

interface BaseChoiceProps {
    questionId: number;
    choices: Choice[];
    answers: Record<number, AnswerValue>;
    onAnswerChange: (questionId: number, value: AnswerValue) => void;
}

/* ---------- Subcomponents ---------- */

const TakeQuestionMultiple: React.FC<BaseChoiceProps> = ({ questionId, choices, answers, onAnswerChange }) => {
    const current = Array.isArray(answers[questionId]) ? (answers[questionId] as number[]) : [];

    const toggleChoice = (choiceId: number, checked: boolean) => {
        if (checked) onAnswerChange(questionId, [...current, choiceId]);
        else onAnswerChange(questionId, current.filter((id) => id !== choiceId));
    };

    return (
        <div className="space-y-3 flex flex-col w-full">
            {choices.map((choice, idx) => (
                <Checkbox
                    key={choice.id}
                    type="checkbox"
                    label={<>
                        {questionIndexLabel(idx, 'bg-blue-100 text-blue-800')}
                        <MarkdownRenderer>{choice.content}</MarkdownRenderer>
                    </>}
                    checked={current.includes(choice.id)}
                    onChange={(e) => toggleChoice(choice.id, e.target.checked)}
                    value={choice.id}
                    labelClassName="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors w-full"
                />
            ))}
        </div>
    );
};

const TakeQuestionOneChoice: React.FC<BaseChoiceProps> = ({ questionId, choices, answers, onAnswerChange }) => {
    const onChange = (value: number) => onAnswerChange(questionId, value);

    return (
        <div className="space-y-3 flex flex-col w-full">
            {choices.map((choice, idx) => (
                <Checkbox
                    key={choice.id}
                    type="radio"
                    name={`question_${questionId}`}
                    label={<>
                        {questionIndexLabel(idx)}
                        <MarkdownRenderer>{choice.content}</MarkdownRenderer>
                    </>}
                    checked={answers[questionId] === choice.id}
                    onChange={() => onChange(choice.id)}
                    value={choice.id}
                    labelClassName="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors w-full"
                />
            ))}
        </div>
    );
};

const TakeQuestionBoolean: React.FC<BaseChoiceProps> = ({ questionId, choices, answers, onAnswerChange }) => {
    const { getBooleanLabel, getBooleanShortLabel } = useChoiceUtils();
    const onChange = (value: number) => onAnswerChange(questionId, value);

    return (
        <div className="space-y-3 flex flex-col w-full">
            {choices.map((choice) => {
                const isTrue = getBooleanDisplay(choice.content || '');
                const badgeClass = getBooleanBadgeClass(isTrue);
                const labelText = getBooleanLabel(isTrue);
                const shortLabel = getBooleanShortLabel(isTrue);

                return (
                    <Checkbox
                        key={choice.id}
                        type="radio"
                        name={`question_${questionId}`}
                        checked={answers[questionId] === choice.id}
                        onChange={() => onChange(choice.id)}
                        value={choice.id}
                        labelClassName="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors w-full"
                        label={<>
                            <span className={`inline-flex items-center justify-center h-6 w-6 rounded-full ${badgeClass} text-xs font-medium mr-2`}>
                                {shortLabel}
                            </span>
                            <span className="text-gray-900">{labelText}</span>
                        </>}
                    />
                );
            })}
        </div>
    );
};

const TakeQuestionText: React.FC<{
    questionId: number;
    answers: Record<number, AnswerValue>;
    onAnswerChange: (questionId: number, value: AnswerValue) => void;
}> = ({ questionId, answers, onAnswerChange }) => {
    const { t } = useTranslations();

    return (
    <div>
        <MarkdownEditor
            enableGuide={false}
            enableMathDisplay={true}
            enableMathInline={true}
            editorClassName="min-h-[150px] sm:min-h-[200px]"
            value={typeof answers[questionId] === 'string' ? (answers[questionId] as string) : ''}
            onChange={(value) => onAnswerChange(questionId, value)}
            placeholder={t('components.take_question.your_answer_placeholder')}
            rows={6}
            helpText={t('components.take_question.your_answer_help')}
        />
    </div>
    );
};

/* ---------- Main Component ---------- */

const TakeQuestion: React.FC<TakeQuestionProps> = ({ question, answers, onAnswerChange }) => {
    const { t } = useTranslations();
    const { getTypeLabel } = useQuestionTypeUtils();

    return (
        <Section
            key={question.id}
            className="relative"
            title={<MarkdownRenderer>{question.content}</MarkdownRenderer>}
            actions={
                <div className="flex space-x-2 top-5 right-5 absolute">
                    <div className="text-sm min-w-fit font-medium h-max text-blue-600 px-2 py-1 rounded">
                        {t('components.take_question.points', { points: question.points })}
                    </div>

                    <span className={`text-xs px-2 py-1 min-w-fit h-max rounded-full ${getTypeColor(question.type)}`}>
                        {getTypeLabel(question.type)}
                    </span>
                </div>
            }
        >
            {question.type === 'multiple' && (
                <TakeQuestionMultiple questionId={question.id} choices={question.choices ?? []} answers={answers} onAnswerChange={onAnswerChange} />
            )}

            {question.type === 'one_choice' && (
                <TakeQuestionOneChoice questionId={question.id} choices={question.choices ?? []} answers={answers} onAnswerChange={onAnswerChange} />
            )}

            {question.type === 'boolean' && (
                <TakeQuestionBoolean questionId={question.id} choices={question.choices ?? []} answers={answers} onAnswerChange={onAnswerChange} />
            )}

            {question.type === 'text' && (
                <TakeQuestionText questionId={question.id} answers={answers} onAnswerChange={onAnswerChange} />
            )}
        </Section>
    );
};

export { TakeQuestion, TakeQuestionMultiple, TakeQuestionOneChoice, TakeQuestionBoolean, TakeQuestionText };
