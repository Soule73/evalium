import { type Choice, type Question, type QuestionResult } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useChoiceUtils } from '@/hooks/shared/useChoiceUtils';
import { Checkbox } from '@evalium/ui';
import { AlertEntry, MarkdownRenderer } from '@evalium/ui';
import { CheckIcon } from '@heroicons/react/16/solid';
import {
    getBooleanDisplay,
    getBooleanBadgeClass,
    getChoiceStyles,
    getChoiceBorder,
} from '@/utils/assessment/components/choiceUtils';
import {
    questionIndexLabel,
    getIndexBgClass,
} from '@/utils/assessment/components/questionLabelUtils';
import { useQuestionContext } from '../QuestionContext';

interface ChoiceRendererProps {
    question: Question;
    result: QuestionResult;
}

type ChoiceOnlyType = 'one_choice' | 'multiple' | 'boolean';

/**
 * Renders choice-based questions (one_choice, multiple, boolean) in both interactive
 * and read-only modes. Reads render config from QuestionContext.
 */
export function ChoiceRenderer({ question, result }: ChoiceRendererProps) {
    const { config, answers, onAnswerChange, isDisabled } = useQuestionContext();
    const choices = question.choices ?? [];
    const type = question.type as ChoiceOnlyType;

    if (config.isInteractive) {
        return (
            <InteractiveChoiceList
                question={question}
                choices={choices}
                type={type}
                answers={answers}
                onAnswerChange={onAnswerChange}
                disabled={isDisabled}
            />
        );
    }

    return (
        <ReadOnlyChoiceList
            choices={choices}
            userChoices={result.userChoices}
            type={type}
            showCorrectAnswers={config.showCorrectAnswers}
            isTeacherView={config.labelVariant === 'teacher'}
            hasResponse={result.userChoices.length > 0}
            suppressNoAnswerWarning={config.suppressNoAnswerWarning}
        />
    );
}

interface InteractiveChoiceListProps {
    question: Question;
    choices: Choice[];
    type: ChoiceOnlyType;
    answers: Record<number, string | number | number[]>;
    onAnswerChange?: (questionId: number, value: string | number | number[]) => void;
    disabled: boolean;
}

function InteractiveChoiceList({
    question,
    choices,
    type,
    answers,
    onAnswerChange,
    disabled,
}: InteractiveChoiceListProps) {
    const { getBooleanLabel, getBooleanShortLabel } = useChoiceUtils();

    const currentMultiple = Array.isArray(answers[question.id])
        ? (answers[question.id] as number[])
        : [];

    const handleMultipleToggle = (choiceId: number, checked: boolean) => {
        if (disabled) return;
        const next = checked
            ? [...currentMultiple, choiceId]
            : currentMultiple.filter((id) => id !== choiceId);
        onAnswerChange?.(question.id, next);
    };

    const handleSingleSelect = (choiceId: number) => {
        if (disabled) return;
        onAnswerChange?.(question.id, choiceId);
    };

    return (
        <div className="space-y-3 flex flex-col w-full">
            {choices.map((choice, idx) => {
                if (type === 'multiple') {
                    return (
                        <Checkbox
                            key={choice.id}
                            type="checkbox"
                            label={
                                <>
                                    {questionIndexLabel(idx, 'bg-indigo-100 text-indigo-800')}
                                    <MarkdownRenderer>{choice.content}</MarkdownRenderer>
                                </>
                            }
                            checked={currentMultiple.includes(choice.id)}
                            onChange={(e) => handleMultipleToggle(choice.id, e.target.checked)}
                            value={choice.id}
                            disabled={disabled}
                            labelClassName="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors w-full"
                        />
                    );
                }

                if (type === 'boolean') {
                    const isTrue = getBooleanDisplay(choice.content || '');
                    const badgeClass = getBooleanBadgeClass(isTrue);
                    const labelText = getBooleanLabel(isTrue);
                    const shortLabel = getBooleanShortLabel(isTrue);

                    return (
                        <Checkbox
                            key={choice.id}
                            type="radio"
                            name={`question_${question.id}`}
                            checked={answers[question.id] === choice.id}
                            onChange={() => handleSingleSelect(choice.id)}
                            value={choice.id}
                            disabled={disabled}
                            labelClassName="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors w-full"
                            label={
                                <>
                                    <span
                                        className={`inline-flex items-center justify-center h-6 w-6 rounded-full ${badgeClass} text-xs font-medium mr-2`}
                                    >
                                        {shortLabel}
                                    </span>
                                    <span className="text-gray-900">{labelText}</span>
                                </>
                            }
                        />
                    );
                }

                return (
                    <Checkbox
                        key={choice.id}
                        type="radio"
                        name={`question_${question.id}`}
                        label={
                            <>
                                {questionIndexLabel(idx)}
                                <MarkdownRenderer>{choice.content}</MarkdownRenderer>
                            </>
                        }
                        checked={answers[question.id] === choice.id}
                        onChange={() => handleSingleSelect(choice.id)}
                        value={choice.id}
                        disabled={disabled}
                        labelClassName="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors w-full"
                    />
                );
            })}
        </div>
    );
}

interface ReadOnlyChoiceListProps {
    choices: Choice[];
    userChoices: Choice[];
    type: ChoiceOnlyType;
    showCorrectAnswers: boolean;
    isTeacherView: boolean;
    hasResponse: boolean;
    suppressNoAnswerWarning: boolean;
}

interface CorrectAnswersListProps {
    choices: Choice[];
    type: ChoiceOnlyType;
    getBooleanLabel: (isTrue: boolean) => string;
    getBooleanShortLabel: (isTrue: boolean) => string;
    getBooleanDisplay: (content: string) => boolean;
    getBooleanBadgeClass: (isTrue: boolean, isHighlighted: boolean) => string;
}

function CorrectAnswersList({
    choices,
    type,
    getBooleanLabel,
    getBooleanShortLabel,
    getBooleanDisplay,
    getBooleanBadgeClass,
}: CorrectAnswersListProps) {
    return (
        <div className="space-y-2">
            {choices.map((choice, idx) => {
                const isCorrect = choice.is_correct;
                const border = getChoiceBorder(type);

                const indexBadge =
                    type === 'boolean'
                        ? (() => {
                              const isTrue = getBooleanDisplay(choice.content || '');
                              const badgeClass = getBooleanBadgeClass(isTrue, isCorrect);
                              const shortLabel = getBooleanShortLabel(isTrue);
                              return (
                                  <span
                                      className={`inline-flex items-center justify-center h-6 w-6 rounded-full ${badgeClass} text-xs font-medium mr-2`}
                                  >
                                      {shortLabel}
                                  </span>
                              );
                          })()
                        : questionIndexLabel(idx, getIndexBgClass(isCorrect, false, true));

                return (
                    <div
                        key={choice.id}
                        className={`p-3 rounded-lg border ${isCorrect ? 'bg-green-50 border-green-200' : 'bg-white border-gray-200'}`}
                    >
                        <div className="flex items-center">
                            <div
                                className={`w-4 h-4 mr-3 flex items-center justify-center ${border} ${isCorrect ? 'border-green-500 bg-green-500' : 'border-gray-300'}`}
                            >
                                {isCorrect && <CheckIcon className="w-4 h-4 fill-white" />}
                            </div>
                            {indexBadge}
                            <span className={isCorrect ? 'text-green-800' : 'text-gray-700'}>
                                {type === 'boolean'
                                    ? getBooleanLabel(getBooleanDisplay(choice.content || ''))
                                    : choice.content}
                            </span>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

function ReadOnlyChoiceList({
    choices,
    userChoices,
    type,
    showCorrectAnswers,
    isTeacherView,
    hasResponse,
    suppressNoAnswerWarning,
}: ReadOnlyChoiceListProps) {
    const { t } = useTranslations();
    const { getBooleanLabel, getBooleanShortLabel, getStatusLabelText } = useChoiceUtils();
    const shouldShowCorrect = showCorrectAnswers;

    if (!hasResponse && !suppressNoAnswerWarning) {
        return (
            <div className="space-y-3">
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
                {shouldShowCorrect && (
                    <CorrectAnswersList
                        choices={choices}
                        type={type}
                        getBooleanLabel={getBooleanLabel}
                        getBooleanShortLabel={getBooleanShortLabel}
                        getBooleanDisplay={getBooleanDisplay}
                        getBooleanBadgeClass={getBooleanBadgeClass}
                    />
                )}
            </div>
        );
    }

    return (
        <div className="space-y-2">
            {choices.map((choice, idx) => {
                const isSelected = userChoices.some((uc) => uc.id === choice.id);
                const isCorrect = choice.is_correct;
                const styles = getChoiceStyles(isSelected, isCorrect, shouldShowCorrect);
                const border = getChoiceBorder(type);
                const statusLabelText = getStatusLabelText(
                    isSelected,
                    isCorrect,
                    shouldShowCorrect,
                    isTeacherView,
                );

                const indexBadge =
                    type === 'boolean'
                        ? (() => {
                              const isTrue = getBooleanDisplay(choice.content || '');
                              const badgeClass = getBooleanBadgeClass(
                                  isTrue,
                                  shouldShowCorrect && isCorrect,
                              );
                              const shortLabel = getBooleanShortLabel(isTrue);
                              return (
                                  <span
                                      className={`inline-flex items-center justify-center h-6 w-6 rounded-full ${badgeClass} text-xs font-medium mr-2`}
                                  >
                                      {shortLabel}
                                  </span>
                              );
                          })()
                        : questionIndexLabel(
                              idx,
                              getIndexBgClass(isCorrect, isSelected, shouldShowCorrect),
                          );

                return (
                    <div key={choice.id} className={`p-3 rounded-lg border ${styles.bg}`}>
                        <div className="flex items-center">
                            <div
                                className={`w-4 h-4 mr-3 flex items-center justify-center ${border} ${styles.borderColor}`}
                            >
                                {(isSelected || (shouldShowCorrect && isCorrect)) && (
                                    <CheckIcon className="w-4 h-4 fill-white" />
                                )}
                            </div>
                            {indexBadge}
                            <span className={styles.text}>
                                {type === 'boolean'
                                    ? getBooleanLabel(getBooleanDisplay(choice.content || ''))
                                    : choice.content}
                            </span>
                            {statusLabelText && (
                                <span
                                    className={`ml-2 text-xs font-medium ${
                                        isSelected && !isCorrect
                                            ? 'text-red-600'
                                            : isCorrect
                                              ? 'text-green-600'
                                              : 'text-indigo-600'
                                    }`}
                                >
                                    {statusLabelText}
                                </span>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
