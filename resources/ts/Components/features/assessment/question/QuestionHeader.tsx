import { CheckCircleIcon, XMarkIcon } from '@heroicons/react/16/solid';
import { type Question, type QuestionResult } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useFormatters } from '@/hooks/shared/useFormatters';
import { MarkdownRenderer } from '@evalium/ui';
import { getTypeColor } from '@/utils/assessment/components';
import { useQuestionContext } from './QuestionContext';

interface QuestionHeaderProps {
    question: Question;
    questionIndex?: number;
    result: QuestionResult;
}

/**
 * Unified question header for all render modes.
 * Displays index, correct/incorrect indicator (when available), score/points, type badge,
 * and the question content. Adapts to interactive vs read-only context automatically.
 */
export function QuestionHeader({ question, questionIndex, result }: QuestionHeaderProps) {
    const { t } = useTranslations();
    const { getQuestionTypeLabel } = useFormatters();
    const { config } = useQuestionContext();

    const displayScore =
        config.isInteractive || result.score === undefined || result.score === null
            ? `${question.points}`
            : `${parseFloat(Number(result.score).toFixed(2))} / ${question.points}`;

    return (
        <div>
            <div className="flex items-start justify-between mb-4">
                <h3 className="text-base font-medium text-gray-900">
                    {questionIndex !== undefined && (
                        <span className="mr-1 text-gray-500">{`Q${questionIndex + 1}.`}</span>
                    )}
                </h3>

                <div className="flex items-center gap-2 shrink-0 ml-4">
                    {!config.isInteractive && result.isCorrect === true && (
                        <span className="text-green-600 text-sm font-medium flex items-center gap-1">
                            <CheckCircleIcon className="w-4 h-4" />
                            {t('components.question_readonly_section.correct')}
                        </span>
                    )}
                    {!config.isInteractive && result.isCorrect === false && (
                        <span className="text-red-600 text-sm font-medium flex items-center gap-1">
                            <XMarkIcon className="w-4 h-4" />
                            {t('components.question_readonly_section.incorrect')}
                        </span>
                    )}

                    <span className="text-sm text-gray-500">{displayScore} pts</span>

                    <span
                        className={`text-xs px-2 py-1 min-w-fit rounded-full ${getTypeColor(question.type)}`}
                    >
                        {getQuestionTypeLabel(question.type)}
                    </span>
                </div>
            </div>

            <div className="mb-4">
                <MarkdownRenderer className="text-gray-700">{question.content}</MarkdownRenderer>
            </div>
        </div>
    );
}
