import { type Question } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { requiresManualGrading } from '@/utils';
import { Textarea } from '@/Components/ui';
import { useQuestionContext } from '../QuestionContext';

interface ScoreInputProps {
    question: Question;
}

/**
 * Editable score and feedback inputs for a single question in 'grade' mode.
 * Reads and writes through QuestionContext handlers.
 */
export function ScoreInput({ question }: ScoreInputProps) {
    const { t } = useTranslations();
    const { config, scoreOverrides, onScoreChange, feedbackOverrides, onFeedbackChange } =
        useQuestionContext();

    if (!config.showScoreInput) {
        return null;
    }

    const maxScore = question.points ?? 0;
    const currentScore = scoreOverrides?.[question.id] ?? 0;
    const currentFeedback = feedbackOverrides?.[question.id] ?? '';
    const isManual = requiresManualGrading(question);

    return (
        <div className="mt-4 p-4 bg-gray-50 rounded-lg space-y-4">
            <div className="flex items-center justify-between">
                <div>
                    <label className="text-sm font-medium text-gray-700">
                        {t('grading_pages.show.question_score_label', { max: maxScore })}
                    </label>
                    {!isManual && (
                        <p className="text-xs text-indigo-600 mt-1">
                            {t('grading_pages.show.auto_graded_info')}
                        </p>
                    )}
                    {isManual && (
                        <p className="text-xs text-orange-600 mt-1">
                            {t('grading_pages.show.manual_grading_required')}
                        </p>
                    )}
                </div>
                <div className="flex items-center space-x-2">
                    <input
                        type="number"
                        min="0"
                        max={maxScore}
                        step="0.5"
                        value={currentScore}
                        onChange={(e) =>
                            onScoreChange?.(question.id, parseFloat(e.target.value) || 0)
                        }
                        className="w-20 px-2 py-1 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        disabled={!config.canEditScores}
                    />
                    <span className="text-sm text-gray-500">/ {maxScore}</span>
                </div>
            </div>

            <Textarea
                label={t('grading_pages.show.question_comment_label')}
                value={currentFeedback}
                onChange={(e) => onFeedbackChange?.(question.id, e.target.value)}
                placeholder={t('grading_pages.show.question_comment_placeholder')}
                rows={2}
                disabled={!config.canEditScores}
            />
        </div>
    );
}
