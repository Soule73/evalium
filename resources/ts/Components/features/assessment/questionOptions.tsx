import { QuestionType } from "@/types";
import { trans } from '@/utils';

export interface QuestionOption {
    key: QuestionType;
    title: string;
    subtitle: string;
    bg: string;
    text: string;
    hoverBg: string;
    svg: React.ReactNode;
}

export const useQuestionOptions = (): QuestionOption[] => {
    return [
        {
            key: 'multiple',
            title: trans('components.question_options.multiple_title'),
            subtitle: trans('components.question_options.multiple_subtitle'),
            bg: 'bg-blue-100',
            text: 'text-blue-600',
            hoverBg: 'group-hover:bg-blue-200',
            svg: (
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="7" cy="8" r="1.5" fill="currentColor" stroke="currentColor" strokeWidth="1" />
                    <polyline points="6.3,8 7,8.7 8.2,7.5" fill="none" stroke="#fff" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round" />
                    <line x1="10" y1="8" x2="20" y2="8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                    <circle cx="7" cy="16" r="1.5" fill="none" stroke="currentColor" strokeWidth="1.5" />
                    <line x1="10" y1="16" x2="20" y2="16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
            ),
        },
        {
            key: 'one_choice',
            title: trans('components.question_options.one_choice_title'),
            subtitle: trans('components.question_options.one_choice_subtitle'),
            bg: 'bg-green-100',
            text: 'text-green-600',
            hoverBg: 'group-hover:bg-green-200',
            svg: (
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7" />
                </svg>
            ),
        },
        {
            key: 'boolean',
            title: trans('components.question_options.boolean_title'),
            subtitle: trans('components.question_options.boolean_subtitle'),
            bg: 'bg-purple-100',
            text: 'text-purple-600',
            hoverBg: 'group-hover:bg-purple-200',
            svg: (
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            ),
        },
        {
            key: 'text',
            title: trans('components.question_options.text_title'),
            subtitle: trans('components.question_options.text_subtitle'),
            bg: 'bg-yellow-100',
            text: 'text-yellow-600',
            hoverBg: 'group-hover:bg-yellow-200',
            svg: (
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            ),
        },
    ];
};
