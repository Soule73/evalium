import {
    CheckCircleIcon,
    Squares2X2Icon,
    QuestionMarkCircleIcon,
    PencilIcon,
} from '@heroicons/react/24/outline';
import type { ComponentType, SVGProps } from 'react';

export interface IconConfig {
    icon: ComponentType<SVGProps<SVGSVGElement>>;
    bgColor: string;
    textColor: string;
}

export const QUESTION_TYPE_CONFIG: Record<string, IconConfig> = {
    multiple: {
        icon: Squares2X2Icon,
        bgColor: 'bg-blue-100',
        textColor: 'text-blue-600',
    },
    one_choice: {
        icon: CheckCircleIcon,
        bgColor: 'bg-green-100',
        textColor: 'text-green-600',
    },
    boolean: {
        icon: QuestionMarkCircleIcon,
        bgColor: 'bg-purple-100',
        textColor: 'text-purple-600',
    },
    text: {
        icon: PencilIcon,
        bgColor: 'bg-yellow-100',
        textColor: 'text-yellow-600',
    },
};

export function getQuestionTypeIcon(type: string): IconConfig | null {
    return QUESTION_TYPE_CONFIG[type] || null;
}
