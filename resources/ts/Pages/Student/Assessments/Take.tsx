import { Head } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import {
    AlertEntry,
    AlertSecurityViolation,
    CanNotTakeAssessment,
    ConfirmationModal,
    FullscreenModal,
    QuestionNavigation,
    Section,
    TakeQuestion,
    TakeReviewStep,
    useToast,
} from '@/Components';
import { type Answer, type Assessment, type AssessmentAssignment, type Question } from '@/types';
import {
    ArrowsPointingOutIcon,
    ClockIcon,
    ExclamationCircleIcon,
    QuestionMarkCircleIcon,
} from '@heroicons/react/24/outline';
import { useTakeAssessment, useQuestionNavigation } from '@/hooks/features/assessment';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatTime } from '@/utils';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

function getTimerClasses(timeLeft: number, durationMinutes: number | null): string {
    if (!durationMinutes || durationMinutes <= 0) {
        return 'text-gray-700 font-semibold tabular-nums';
    }
    const percent = timeLeft / (durationMinutes * 60);
    if (percent <= 0.1) {
        return 'text-red-600 text-lg font-bold tabular-nums animate-pulse';
    }
    if (percent <= 0.25) {
        return 'text-amber-500 font-bold tabular-nums';
    }
    return 'text-gray-600 text-sm font-semibold tabular-nums';
}

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
    const { addToast } = useToast();

    useEffect(() => {
        const key = `instructions_shown_${assignment.id}`;
        if (!sessionStorage.getItem(key)) {
            addToast({
                type: 'warning',
                title: t('student_assessment_pages.take.attention'),
                message: t('student_assessment_pages.take.toast_instructions'),
                autoClose: true,
                duration: 4000,
            });
            sessionStorage.setItem(key, '1');
        }
    }, [addToast, assignment.id, t]);

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
        wizardStep,
        setWizardStep,
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
            wizardStep: state.wizardStep,
            setWizardStep: state.setWizardStep,
        })),
    );

    const {
        displayedQuestions,
        orderedQuestions,
        currentQuestionIndex,
        totalQuestions,
        isFirstQuestion,
        isLastQuestion,
        handleNextQuestion,
        handlePreviousQuestion,
        goToQuestion,
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
            fullscreenExitWarning: t('student_assessment_pages.take.fullscreen_exit_warning'),
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

    const handleGoToQuestionInWizard = (index: number) => {
        setWizardStep('answering');
        goToQuestion(index);
    };

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
                className="bg-white py-3 border-b border-gray-200 fixed w-full z-10 top-0"
            >
                <div className="container mx-auto flex items-center justify-between gap-4 px-4">
                    <h1 className="text-sm font-semibold text-gray-900 truncate min-w-0">
                        {assessment.title}
                    </h1>

                    <div className="flex items-center gap-1.5 shrink-0">
                        <ClockIcon className="h-4 w-4 text-gray-400" />
                        <span
                            className={getTimerClasses(
                                timeLeft,
                                assessment.duration_minutes ?? null,
                            )}
                        >
                            {formatTime(timeLeft)}
                        </span>
                    </div>

                    {fullscreenRequired && !security.isFullscreen && (
                        <button
                            type="button"
                            onClick={enterFullscreen}
                            title={translations.fullscreenExitWarning}
                            className="shrink-0 p-1.5 rounded-md text-amber-600 hover:bg-amber-50 animate-pulse"
                        >
                            <ArrowsPointingOutIcon className="h-5 w-5" />
                        </button>
                    )}
                </div>
            </header>

            <div className="pt-14 max-w-3xl mx-auto px-4 py-8">
                {!assessmentCanStart && (
                    <Section title={translations.fullscreenActivationTitle} collapsible={false}>
                        <AlertEntry type="info" title={translations.attention}>
                            <p>{translations.fullscreenActivationMessage}</p>
                        </AlertEntry>
                    </Section>
                )}

                {assessmentCanStart &&
                    wizardStep === 'answering' &&
                    displayedQuestions.length > 0 && (
                        <>
                            <div className="mb-4 text-center">
                                <span className="text-sm font-medium text-gray-500">
                                    {t('student_assessment_pages.take.question_progress', {
                                        current: currentQuestionIndex + 1,
                                        total: totalQuestions,
                                    })}
                                </span>
                            </div>

                            {displayedQuestions.map((currentQ) => (
                                <TakeQuestion
                                    key={currentQ.id}
                                    question={currentQ}
                                    answers={answers}
                                    onAnswerChange={handleAnswerChange}
                                />
                            ))}

                            <div className="mt-2 min-h-5">
                                {saveStatus === 'saving' && (
                                    <p className="text-xs text-gray-400">{translations.saving}</p>
                                )}
                                {saveStatus === 'saved' && (
                                    <p className="text-xs text-green-500">{translations.saved}</p>
                                )}
                                {saveStatus === 'error' && (
                                    <p className="text-xs text-red-500">{translations.saveError}</p>
                                )}
                            </div>
                        </>
                    )}

                {assessmentCanStart && wizardStep === 'reviewing' && (
                    <TakeReviewStep
                        questions={orderedQuestions}
                        answers={answers}
                        totalQuestions={totalQuestions}
                        onGoToQuestion={handleGoToQuestionInWizard}
                        onConfirmSubmit={() => setShowConfirmModal(true)}
                        onBack={() => {
                            goToQuestion(totalQuestions - 1);
                            setWizardStep('answering');
                        }}
                        isSubmitting={isSubmitting || processing}
                    />
                )}
            </div>

            {assessmentCanStart && wizardStep === 'answering' && (
                <QuestionNavigation
                    currentIndex={currentQuestionIndex}
                    totalQuestions={totalQuestions}
                    isFirstQuestion={isFirstQuestion}
                    isLastQuestion={isLastQuestion}
                    onPrevious={handlePreviousQuestion}
                    onNext={handleNextQuestion}
                    onFinish={() => setWizardStep('reviewing')}
                    onGoToQuestion={goToQuestion}
                    answeredQuestions={answeredQuestionIds}
                    questionIds={shuffledQuestionIds}
                />
            )}

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
