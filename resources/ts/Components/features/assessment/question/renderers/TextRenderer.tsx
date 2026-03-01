import { type Question, type QuestionResult } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AlertEntry, MarkdownEditor, MarkdownRenderer } from '@evalium/ui';
import { useQuestionContext } from '../QuestionContext';

interface TextRendererProps {
    question: Question;
    result: QuestionResult;
}

/**
 * Renders text-based questions.
 * In 'take' mode: shows a MarkdownEditor for the student to write a response.
 * In read-only modes: displays the submitted answer with a contextual label.
 */
export function TextRenderer({ question, result }: TextRendererProps) {
    const { t } = useTranslations();
    const { config, answers, onAnswerChange, isDisabled } = useQuestionContext();

    if (config.isInteractive) {
        return (
            <MarkdownEditor
                enableGuide={false}
                enableMathDisplay={true}
                enableMathInline={true}
                editorClassName="min-h-[150px] sm:min-h-[200px]"
                value={
                    typeof answers[question.id] === 'string' ? (answers[question.id] as string) : ''
                }
                onChange={(value) => onAnswerChange?.(question.id, value)}
                placeholder={t('components.take_question.your_answer_placeholder')}
                rows={6}
                disabled={isDisabled}
                helpText={t('components.take_question.your_answer_help')}
            />
        );
    }

    if (!result.userText && !config.suppressNoAnswerWarning) {
        return (
            <AlertEntry
                title={t('components.question_renderer.no_answer')}
                type="warning"
                className="mt-2"
            >
                <p className="text-sm">
                    {config.labelVariant === 'teacher'
                        ? t('components.question_renderer.no_answer_student')
                        : t('components.question_renderer.no_answer_yours')}
                </p>
            </AlertEntry>
        );
    }

    const label =
        config.labelVariant === 'teacher'
            ? t('components.question_renderer.student_answer_label')
            : t('components.question_result_readonly.your_answer_default');

    return (
        <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <p className="text-sm text-gray-600 mb-1">{label}</p>
            <MarkdownRenderer>
                {result.userText || t('components.question_renderer.no_answer')}
            </MarkdownRenderer>
        </div>
    );
}
