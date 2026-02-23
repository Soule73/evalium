import { Head } from '@inertiajs/react';
import { useMemo } from 'react';
import {
    AlertEntry,
    AlertSecurityViolation,
    Button,
    CanNotTakeAssessment,
    ConfirmationModal,
    FullscreenModal,
    QuestionNavigation,
    Section,
    TakeQuestion,
    TextEntry,
} from '@/Components';
import { type Answer, type Assessment, type AssessmentAssignment, type Question } from '@/types';
import { ExclamationCircleIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import { useTakeAssessment, useQuestionNavigation } from '@/hooks/features/assessment';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatTime, getTimeColorClass, isTimePulsing } from '@/utils';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

interface TakeAssessmentProps {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    questions: Question[];
    userAnswers: Answer[];
    remainingSeconds: number | null;
}

function Take({
    assessment,
    assignment,
    questions = [],
    userAnswers = [],
    remainingSeconds = null,
}: TakeAssessmentProps) {
    const { t } = useTranslations();

    const {
        answers,
        timeLeft,
        showConfirmModal,
        setShowConfirmModal,
        isSubmitting,
        assessmentTerminated,
        terminationReason,
        showFullscreenModal,
        shuffledQuestionIds,
    } = useAssessmentTakeStore(
        useShallow((state) => ({
            answers: state.answers,
            timeLeft: state.timeLeft,
            showConfirmModal: state.showConfirmModal,
            setShowConfirmModal: state.setShowConfirmModal,
            isSubmitting: state.isSubmitting,
            assessmentTerminated: state.assessmentTerminated,
            terminationReason: state.terminationReason,
            showFullscreenModal: state.showFullscreenModal,
            shuffledQuestionIds: state.shuffledQuestionIds,
        })),
    );

    const {
        displayedQuestions,
        currentQuestionIndex,
        totalQuestions,
        isFirstQuestion,
        isLastQuestion,
        handleNextQuestion,
        handlePreviousQuestion,
        goToQuestion,
        oneQuestionPerPage,
    } = useQuestionNavigation({
        questions,
        shuffleEnabled: assessment.shuffle_questions ?? false,
        oneQuestionPerPage: assessment.one_question_per_page ?? false,
        enforceOnePerPage: true,
    });

    const answeredQuestionIds = useMemo(() => {
        return new Set(
            Object.keys(answers)
                .map(Number)
                .filter((id) => {
                    const answer = answers[id];
                    if (Array.isArray(answer)) return answer.length > 0;
                    return answer !== undefined && answer !== '' && answer !== null;
                }),
        );
    }, [answers]);

    const translations = useMemo(
        () => ({
            assessmentTerminatedTitle: t(
                'student_assessment_pages.take.assessment_terminated_title',
            ),
            assessmentAlreadySubmittedTitle: t(
                'student_assessment_pages.take.assessment_already_submitted_title',
            ),
            assessmentAlreadySubmitted: t(
                'student_assessment_pages.take.assessment_already_submitted',
            ),
            noQuestionsTitle: t('student_assessment_pages.take.no_questions_title'),
            noQuestionsSubtitle: t('student_assessment_pages.take.no_questions_subtitle'),
            noQuestionsMessage: t('student_assessment_pages.take.no_questions_message'),
            timeRemaining: t('student_assessment_pages.take.time_remaining'),
            fullscreenExitWarning: t('student_assessment_pages.take.fullscreen_exit_warning'),
            submitting: t('student_assessment_pages.take.submitting'),
            finishAssessment: t('student_assessment_pages.take.finish_assessment'),
            importantInstructions: t('student_assessment_pages.take.important_instructions'),
            warningTitle: t('student_assessment_pages.take.warning_title'),
            warningMessage1: t('student_assessment_pages.take.warning_message_1'),
            warningMessage2: t('student_assessment_pages.take.warning_message_2'),
            warningMessage3: t('student_assessment_pages.take.warning_message_3'),
            warningAutoSave: t('student_assessment_pages.take.warning_auto_save'),
            fullscreenActivationTitle: t(
                'student_assessment_pages.take.fullscreen_activation_title',
            ),
            attention: t('student_assessment_pages.take.attention'),
            fullscreenActivationMessage: t(
                'student_assessment_pages.take.fullscreen_activation_message',
            ),
            confirmSubmitTitle: t('student_assessment_pages.take.confirm_submit_title'),
            confirmSubmitMessage: t('student_assessment_pages.take.confirm_submit_message'),
            confirmSubmitCheck: t('student_assessment_pages.take.confirm_submit_check'),
            saving: t('student_assessment_pages.take.saving'),
            saved: t('student_assessment_pages.take.saved'),
            saveError: t('student_assessment_pages.take.save_error'),
            modalConfirmText: t('commons/ui.confirm'),
            modalCancelText: t('commons/ui.cancel'),
        }),
        [t],
    );

    const titleTranslation = useMemo(
        () => t('student_assessment_pages.take.title', { assessment: assessment.title }),
        [t, assessment.title],
    );

    const {
        security,
        processing,
        handleAnswerChange,
        handleSubmit,
        enterFullscreen,
        assessmentCanStart,
        fullscreenRequired,
        saveStatus,
    } = useTakeAssessment({
        assessment,
        questions,
        userAnswers,
        remainingSeconds,
    });

    if (assessmentTerminated) {
        return (
            <AlertSecurityViolation
                assessment={assessment}
                reason={
                    terminationReason ||
                    t('student_assessment_pages.take.assessment_terminated_title')
                }
            />
        );
    }

    if (assignment.submitted_at) {
        return (
            <CanNotTakeAssessment
                title={translations.assessmentAlreadySubmittedTitle}
                message={translations.assessmentAlreadySubmitted}
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }

    if (!questions || questions.length === 0) {
        return (
            <CanNotTakeAssessment
                title={translations.noQuestionsTitle}
                subtitle={translations.noQuestionsSubtitle}
                message={translations.noQuestionsMessage}
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }

    return (
        <div className="bg-gray-50 min-h-screen">
            <Head title={titleTranslation} />

            <header
                role="banner"
                className="bg-white py-4 border-b border-gray-200 fixed w-full z-10 top-0"
            >
                <div className="container mx-auto flex justify-between items-center gap-4 px-4">
                    <TextEntry
                        className="text-start min-w-0"
                        label={assessment.title}
                        value={
                            assessment.description
                                ? assessment.description.length > 100
                                    ? assessment.description.substring(0, 100) + '...'
                                    : assessment.description
                                : ''
                        }
                    />

                    <div className="flex flex-col items-center shrink-0">
                        <p className="text-xs font-medium text-gray-500 mb-0.5">
                            {translations.timeRemaining}
                        </p>
                        <span
                            className={[
                                'text-xl font-bold tabular-nums transition-colors duration-700',
                                assessment.duration_minutes
                                    ? getTimeColorClass(timeLeft ?? 0, assessment.duration_minutes)
                                    : 'text-gray-900',
                                assessment.duration_minutes && isTimePulsing(timeLeft ?? 0)
                                    ? 'animate-pulse'
                                    : '',
                            ]
                                .filter(Boolean)
                                .join(' ')}
                        >
                            {formatTime(timeLeft)}
                        </span>
                        {saveStatus === 'saving' && (
                            <span className="text-xs text-gray-400 mt-1">
                                {translations.saving}
                            </span>
                        )}
                        {saveStatus === 'saved' && (
                            <span className="text-xs text-green-500 mt-1">
                                {translations.saved}
                            </span>
                        )}
                        {saveStatus === 'error' && (
                            <span className="text-xs text-red-500 mt-1">
                                {translations.saveError}
                            </span>
                        )}
                    </div>

                    {fullscreenRequired && !security.isFullscreen && (
                        <button
                            type="button"
                            onClick={enterFullscreen}
                            className="text-sm text-amber-600 font-medium animate-pulse hover:text-amber-700 shrink-0"
                        >
                            {translations.fullscreenExitWarning}
                        </button>
                    )}

                    <Button
                        size="sm"
                        color="primary"
                        onClick={() => setShowConfirmModal(true)}
                        disabled={isSubmitting || processing}
                        loading={isSubmitting || processing}
                        aria-label={translations.finishAssessment}
                    >
                        {isSubmitting || processing
                            ? translations.submitting
                            : translations.finishAssessment}
                    </Button>
                </div>
            </header>

            <div className="pt-20 max-w-6xl mx-auto">
                <div className="container mx-auto px-4 py-8">
                    <Section
                        title={translations.importantInstructions}
                        collapsible
                        defaultOpen={true}
                    >
                        <AlertEntry type="warning" title={translations.warningTitle}>
                            <p>
                                {translations.warningMessage1}
                                <strong> {translations.warningMessage2}</strong>{' '}
                                {translations.warningMessage3}
                            </p>
                            <p>{translations.warningAutoSave}</p>
                        </AlertEntry>
                    </Section>

                    {assessmentCanStart &&
                        displayedQuestions.length > 0 &&
                        displayedQuestions.map((currentQ) => (
                            <TakeQuestion
                                key={currentQ.id}
                                question={currentQ}
                                answers={answers}
                                onAnswerChange={handleAnswerChange}
                            />
                        ))}

                    {assessmentCanStart && oneQuestionPerPage && displayedQuestions.length > 0 && (
                        <QuestionNavigation
                            currentIndex={currentQuestionIndex}
                            totalQuestions={totalQuestions}
                            isFirstQuestion={isFirstQuestion}
                            isLastQuestion={isLastQuestion}
                            onPrevious={handlePreviousQuestion}
                            onNext={handleNextQuestion}
                            onGoToQuestion={goToQuestion}
                            answeredQuestions={answeredQuestionIds}
                            questionIds={shuffledQuestionIds}
                        />
                    )}

                    {!assessmentCanStart && (
                        <Section title={translations.fullscreenActivationTitle} collapsible={false}>
                            <AlertEntry type="info" title={translations.attention}>
                                <p>{translations.fullscreenActivationMessage}</p>
                            </AlertEntry>
                        </Section>
                    )}
                </div>
            </div>

            <ConfirmationModal
                title={translations.confirmSubmitTitle}
                message={translations.confirmSubmitMessage}
                icon={QuestionMarkCircleIcon}
                type="info"
                isOpen={showConfirmModal}
                onClose={() => setShowConfirmModal(false)}
                onConfirm={handleSubmit}
                loading={isSubmitting || processing}
                confirmText={translations.modalConfirmText}
                cancelText={translations.modalCancelText}
            >
                <p className="text-gray-600 mb-4 text-center">{translations.confirmSubmitCheck}</p>
                {totalQuestions - answeredQuestionIds.size > 0 && (
                    <p className="text-amber-600 text-sm font-medium text-center mb-2">
                        {t('student_assessment_pages.take.unanswered_warning', {
                            count: totalQuestions - answeredQuestionIds.size,
                        })}
                    </p>
                )}
            </ConfirmationModal>

            <FullscreenModal isOpen={showFullscreenModal} onEnterFullscreen={enterFullscreen} />
        </div>
    );
}

Take.layout = (page: React.ReactNode) => page;

export default Take;
