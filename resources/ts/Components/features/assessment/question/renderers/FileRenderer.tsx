import { type Question, type QuestionResult } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AlertEntry } from '@evalium/ui';
import { FileList } from '@/Components/shared/lists';
import { FileUploadZone } from '../../FileUploadZone';
import { useQuestionContext } from '../QuestionContext';

interface FileRendererProps {
    question: Question;
    result: QuestionResult;
}

/**
 * Renders file-upload questions.
 * In 'take' mode: shows a FileUploadZone for interactive upload.
 * In read-only modes: shows the submitted file using FileList in readOnly mode.
 */
export function FileRenderer({ question, result }: FileRendererProps) {
    const { t } = useTranslations();
    const {
        config,
        assessmentId,
        fileAnswers,
        onFileAnswerSaved,
        onFileAnswerRemoved,
        isDisabled,
    } = useQuestionContext();

    if (config.isInteractive) {
        if (!assessmentId) {
            return null;
        }

        return (
            <FileUploadZone
                assessmentId={assessmentId}
                questionId={question.id}
                fileAnswer={fileAnswers?.[question.id]}
                onFileAnswerSaved={(answer) => onFileAnswerSaved?.(question.id, answer)}
                onFileAnswerRemoved={(answerId) => onFileAnswerRemoved?.(question.id, answerId)}
                disabled={isDisabled}
            />
        );
    }

    if (!result.fileAnswer && !config.suppressNoAnswerWarning) {
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

    if (!result.fileAnswer) {
        return null;
    }

    return (
        <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <p className="text-sm text-gray-600 mb-2">
                {t('components.question_result_readonly.submitted_file')}
            </p>
            <FileList attachments={[result.fileAnswer]} readOnly />
        </div>
    );
}
