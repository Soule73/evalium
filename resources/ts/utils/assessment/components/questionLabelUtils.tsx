import React from 'react';

/**
 * Generates a letter-based index label for question choices
 */
export const questionIndexLabel = (
    idx: number,
    bgClass: string = 'bg-gray-100 text-gray-800'
): React.ReactNode => (
    <span
        className={`inline-flex items-center justify-center h-6 w-6 rounded-full ${bgClass} text-xs font-medium mr-2`}
    >
        {String.fromCharCode(65 + idx)}
    </span>
);

/**
 * Gets the index letter for a choice
 */
export const getChoiceIndexLetter = (index: number): string => {
    return String.fromCharCode(65 + index);
};

/**
 * Gets the background class for a choice index based on correctness
 */
export const getIndexBgClass = (
    isCorrect: boolean,
    isSelected: boolean,
    shouldShowCorrect: boolean
): string => {
    if (!shouldShowCorrect) {
        return 'bg-gray-100 text-gray-800';
    }

    if (isCorrect) {
        return 'bg-green-100 text-green-800';
    }

    if (isSelected) {
        return 'bg-red-100 text-red-800';
    }

    return 'bg-gray-100 text-gray-800';
};
