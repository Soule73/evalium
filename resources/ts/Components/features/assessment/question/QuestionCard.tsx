import { memo, useMemo } from 'react';
import { type Question } from '@/types';
import { AlertEntry } from '@evalium/ui';
import { buildQuestionResult } from '@/utils/assessment/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useQuestionContext } from './QuestionContext';
import { QuestionHeader } from './QuestionHeader';
import { ScoreInput } from './score/ScoreInput';
import { QuestionRenderers } from './renderers';

interface QuestionCardProps {
    question: Question;
    questionIndex?: number;
}

/**
 * Unified question rendering component for all modes.
 * Reads QuestionRenderConfig and answer data from the nearest QuestionProvider.
 * Delegates content rendering to the appropriate type-strategy renderer.
 * Memoized to prevent re-renders when sibling questions change.
 */
export const QuestionCard: React.FC<QuestionCardProps> = memo(({ question, questionIndex }) => {
    const { t } = useTranslations();
    const { config, userAnswers, scoreOverrides, feedbackOverrides } = useQuestionContext();

    const overrides = useMemo(() => {
        if (!scoreOverrides && !feedbackOverrides) return undefined;
        return { scores: scoreOverrides, feedbacks: feedbackOverrides };
    }, [scoreOverrides, feedbackOverrides]);

    const result = useMemo(
        () => buildQuestionResult(question, userAnswers[question.id], overrides),
        [question, userAnswers, overrides],
    );

    const Renderer = QuestionRenderers[question.type];

    return (
        <div className="border border-gray-200 rounded-lg p-6">
            <QuestionHeader question={question} questionIndex={questionIndex} result={result} />

            {Renderer ? (
                <Renderer question={question} result={result} />
            ) : (
                <AlertEntry type="warning" title={t('components.question_renderer.no_answer')} />
            )}

            {result.feedback && !config.showScoreInput && (
                <AlertEntry title={result.feedback} type="info" className="mt-2" />
            )}

            <ScoreInput question={question} />
        </div>
    );
});

QuestionCard.displayName = 'QuestionCard';
