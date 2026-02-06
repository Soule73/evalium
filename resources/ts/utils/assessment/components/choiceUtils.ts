import { trans } from '@/utils';

/**
 * Determines if a boolean choice content represents true
 */
export const getBooleanDisplay = (content: string): boolean => {
    const normalized = content?.toString().toLowerCase() ?? '';
    return ['true', 'vrai'].includes(normalized);
};

/**
 * Gets localized boolean label
 */
export const getBooleanLabel = (isTrue: boolean): string => {
    return isTrue
        ? trans('components.take_question.true')
        : trans('components.take_question.false');
};

/**
 * Gets localized boolean short label (for badges)
 */
export const getBooleanShortLabel = (isTrue: boolean): string => {
    return isTrue
        ? trans('components.question_result_readonly.boolean_true_short')
        : trans('components.question_result_readonly.boolean_false_short');
};

/**
 * Gets badge class for boolean choice
 */
export const getBooleanBadgeClass = (isTrue: boolean, shouldHighlight: boolean = false): string => {
    if (shouldHighlight) {
        return 'bg-green-100 text-green-800';
    }
    return isTrue ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
};

/**
 * Generates styles for choice rendering based on selection and correctness
 */
export const getChoiceStyles = (
    isSelected: boolean,
    isCorrect: boolean,
    shouldShowCorrect: boolean
): { bg: string; text: string; borderColor: string } => {
    if (!shouldShowCorrect) {
        return {
            bg: isSelected ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200',
            text: isSelected ? 'text-blue-800 font-medium' : 'text-gray-700',
            borderColor: isSelected ? 'border-blue-500 bg-blue-500' : 'border-gray-300',
        };
    }

    if (isSelected) {
        return {
            bg: isCorrect ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200',
            text: isCorrect ? 'text-green-800 font-medium' : 'text-red-800 font-medium',
            borderColor: isCorrect ? 'border-green-500 bg-green-500' : 'border-red-500 bg-red-500',
        };
    }

    return {
        bg: isCorrect ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200',
        text: isCorrect ? 'text-green-800 font-medium' : 'text-gray-700',
        borderColor: isCorrect ? 'border-green-500 bg-green-500' : 'border-gray-300',
    };
};

/**
 * Gets the status label for a choice (returns translation key or null)
 */
export const getStatusLabelText = (
    isSelected: boolean,
    isCorrect: boolean,
    shouldShowCorrect: boolean,
    isTeacherView: boolean
): string | null => {
    if (!shouldShowCorrect) {
        return isSelected
            ? isTeacherView
                ? trans('components.question_result_readonly.student_answer')
                : trans('components.question_result_readonly.your_answer')
            : null;
    }

    if (isSelected && !isCorrect) {
        return isTeacherView
            ? trans('components.question_result_readonly.student_answer_incorrect')
            : trans('components.question_result_readonly.your_answer_incorrect');
    }

    if (isSelected && isCorrect) {
        return isTeacherView
            ? trans('components.question_result_readonly.student_answer_correct')
            : trans('components.question_result_readonly.your_answer_correct');
    }

    if (!isSelected && isCorrect) {
        return trans('components.question_result_readonly.correct_answer');
    }

    return null;
};

/**
 * Gets the border style for a choice input (checkbox or radio)
 */
export const getChoiceBorder = (type: 'multiple' | 'one_choice' | 'boolean'): string => {
    return type === 'multiple' ? 'rounded border-2' : 'rounded-full border-2';
};
