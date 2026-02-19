import { type Choice } from '@/types';
import { CheckIcon } from '@heroicons/react/16/solid';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useChoiceUtils } from '@/hooks/shared/useChoiceUtils';
import { MarkdownRenderer } from '@evalium/ui';
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

interface QuestionResultReadOnlyTextProps {
    userText?: string;
    label?: string;
}

const QuestionResultReadOnlyText: React.FC<QuestionResultReadOnlyTextProps> = ({
    userText,
    label,
}) => {
    const { t } = useTranslations();
    const defaultLabel = t('components.question_result_readonly.your_answer_default');

    return (
        <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <p className="text-sm text-gray-600 mb-1">{label || defaultLabel}</p>
            <MarkdownRenderer>
                {userText || t('components.question_renderer.no_answer')}
            </MarkdownRenderer>
        </div>
    );
};

interface ChoiceItemProps {
    choice: Choice;
    index: number;
    type: 'one_choice' | 'multiple' | 'boolean';
    isSelected: boolean;
    shouldShowCorrect: boolean;
    isTeacherView: boolean;
}

const ChoiceItem: React.FC<ChoiceItemProps> = ({
    choice,
    index,
    type,
    isSelected,
    shouldShowCorrect,
    isTeacherView,
}) => {
    const { getBooleanLabel, getBooleanShortLabel, getStatusLabelText } = useChoiceUtils();
    const isCorrect = choice.is_correct;
    const styles = getChoiceStyles(isSelected, isCorrect, shouldShowCorrect);
    const border = getChoiceBorder(type);

    const indexLabel =
        type === 'boolean'
            ? (() => {
                const isTrue = getBooleanDisplay(choice.content || '');
                const badgeClass = getBooleanBadgeClass(isTrue, shouldShowCorrect && isCorrect);
                const shortLabel = getBooleanShortLabel(isTrue);
                return (
                    <span
                        className={`inline-flex items-center justify-center h-6 w-6 rounded-full ${badgeClass} text-xs font-medium mr-2`}
                    >
                        {shortLabel}
                    </span>
                );
            })()
            : questionIndexLabel(index, getIndexBgClass(isCorrect, isSelected, shouldShowCorrect));

    const statusLabelText = getStatusLabelText(
        isSelected,
        isCorrect,
        shouldShowCorrect,
        isTeacherView,
    );

    return (
        <div className={`p-3 rounded-lg border ${styles.bg}`}>
            <div className="flex items-center">
                <div
                    className={`w-4 h-4 mr-3 flex items-center justify-center ${border} ${styles.borderColor}`}
                >
                    {(isSelected || (shouldShowCorrect && isCorrect)) && (
                        <CheckIcon className="w-4 h-4 fill-white" />
                    )}
                </div>
                {indexLabel}
                <span className={styles.text}>
                    {type === 'boolean'
                        ? getBooleanLabel(getBooleanDisplay(choice.content || ''))
                        : choice.content}
                </span>
                {statusLabelText && (
                    <span
                        className={`ml-2 text-xs font-medium ${isSelected && !isCorrect
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
};

interface QuestionResultReadOnlyChoicesProps {
    choices: Choice[];
    userChoices: Choice[];
    type: 'one_choice' | 'multiple' | 'boolean';
    isTeacherView?: boolean;
    showCorrectAnswers?: boolean;
}

const QuestionResultReadOnlyChoices: React.FC<QuestionResultReadOnlyChoicesProps> = ({
    choices,
    userChoices,
    type,
    isTeacherView = false,
    showCorrectAnswers = false,
}) => {
    const shouldShowCorrect = showCorrectAnswers || isTeacherView;

    return (
        <div className="space-y-2">
            {(choices ?? []).map((choice, idx) => {
                const isSelected = userChoices.some((uc) => uc.id === choice.id);

                return (
                    <ChoiceItem
                        key={choice.id}
                        choice={choice}
                        index={idx}
                        type={type}
                        isSelected={isSelected}
                        shouldShowCorrect={shouldShowCorrect}
                        isTeacherView={isTeacherView}
                    />
                );
            })}
        </div>
    );
};

export {
    QuestionResultReadOnlyText,
    QuestionResultReadOnlyChoices,
};
