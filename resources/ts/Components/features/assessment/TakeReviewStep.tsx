import { Button, MarkdownRenderer } from '@evalium/ui';
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ChevronLeftIcon,
} from '@heroicons/react/24/outline';
import { type Question } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';

type AnswerValue = string | number | number[];

interface TakeReviewStepProps {
    questions: Question[];
    answers: Record<number, AnswerValue>;
    totalQuestions: number;
    onGoToQuestion: (index: number) => void;
    onConfirmSubmit: () => void;
    onBack: () => void;
    isSubmitting: boolean;
}

/**
 * Determines whether a question has a valid, non-empty answer.
 */
function isQuestionAnswered(questionId: number, answers: Record<number, AnswerValue>): boolean {
    const answer = answers[questionId];
    if (answer === undefined || answer === null) {
        return false;
    }
    if (typeof answer === 'string') {
        return answer.trim().length > 0;
    }
    if (Array.isArray(answer)) {
        return answer.length > 0;
    }
    return true;
}

/**
 * Review step of the Take wizard. Displays all questions with answered/unanswered
 * status and allows the student to jump back to any question before final submission.
 */
export function TakeReviewStep({
    questions,
    answers,
    totalQuestions,
    onGoToQuestion,
    onConfirmSubmit,
    onBack,
    isSubmitting,
}: TakeReviewStepProps) {
    const { t } = useTranslations();

    const answeredCount = questions.filter((q) => isQuestionAnswered(q.id, answers)).length;
    const unansweredCount = totalQuestions - answeredCount;

    return (
        <div className="flex flex-col gap-6">
            <div className="flex flex-col gap-1">
                <h2 className="text-xl font-bold text-gray-900">
                    {t('student_assessment_pages.review.title')}
                </h2>
                <div className="flex items-center gap-4 text-sm">
                    <span className="flex items-center gap-1 text-green-700 font-medium">
                        <CheckCircleIcon className="h-4 w-4" />
                        {t('student_assessment_pages.review.answered', { count: answeredCount })}
                    </span>
                    {unansweredCount > 0 && (
                        <span className="flex items-center gap-1 text-amber-600 font-medium">
                            <ExclamationTriangleIcon className="h-4 w-4" />
                            {t('student_assessment_pages.review.unanswered', {
                                count: unansweredCount,
                            })}
                        </span>
                    )}
                </div>
            </div>

            <div className="flex flex-col gap-2">
                {questions.map((question, index) => {
                    const answered = isQuestionAnswered(question.id, answers);

                    return (
                        <button
                            key={question.id}
                            type="button"
                            onClick={() => onGoToQuestion(index)}
                            className={`
                                flex items-center justify-between w-full rounded-lg border px-4 py-3
                                text-left transition-colors cursor-pointer border-gray-200
                            `}
                        >
                            <div className="flex items-center gap-3 min-w-0">
                                <span className="shrink-0 text-xs font-semibold text-gray-500 uppercase tracking-wide w-8">
                                    {t('student_assessment_pages.review.question_label', {
                                        number: index + 1,
                                    })}
                                </span>
                                <MarkdownRenderer>{question.content}</MarkdownRenderer>
                            </div>
                            <div className="flex items-center gap-2 shrink-0 ml-4">
                                {answered ? (
                                    <CheckCircleIcon className="h-5 w-5 text-green-600" />
                                ) : (
                                    <span className="text-xs font-medium text-amber-600">
                                        {t('student_assessment_pages.review.go_to_question')}
                                    </span>
                                )}
                            </div>
                        </button>
                    );
                })}
            </div>

            <div className="flex items-center justify-between pt-2">
                <Button
                    variant="outline"
                    onClick={onBack}
                    className="flex items-center gap-2"
                    disabled={isSubmitting}
                >
                    <ChevronLeftIcon className="h-4 w-4" />
                    {t('student_assessment_pages.review.back_to_question')}
                </Button>

                <Button
                    onClick={onConfirmSubmit}
                    disabled={isSubmitting}
                    className="flex items-center gap-2"
                >
                    {t('student_assessment_pages.review.submit_final')}
                </Button>
            </div>
        </div>
    );
}
