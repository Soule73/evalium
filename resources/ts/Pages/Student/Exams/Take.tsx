import { Head } from '@inertiajs/react';
import { AlertEntry, AlertSecurityViolation, Button, CanNotTakeExam, ConfirmationModal, FullscreenModal, Section, TakeQuestion, TextEntry } from '@/Components';
import { Answer, Exam, ExamAssignment, Question } from '@/types';
import { ExclamationCircleIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import { useTakeExam } from '@/hooks';
import { formatTime } from '@/utils';
import { trans } from '@/utils';
import { useMemo } from 'react';
import { useExamTakeStore } from '@/stores/useExamTakeStore';
import { useShallow } from 'zustand/react/shallow';

interface TakeExamProps {
    exam: Exam;
    assignment: ExamAssignment;
    questions: Question[];
    userAnswers: Answer[];
}

export default function Take({ exam, assignment, questions = [], userAnswers = [] }: TakeExamProps) {
    const { answers, timeLeft, showConfirmModal, setShowConfirmModal, isSubmitting, examTerminated, terminationReason, showFullscreenModal, examCanStart } = useExamTakeStore(
        useShallow((state) => ({
            answers: state.answers,
            timeLeft: state.timeLeft,
            showConfirmModal: state.showConfirmModal,
            setShowConfirmModal: state.setShowConfirmModal,
            isSubmitting: state.isSubmitting,
            examTerminated: state.examTerminated,
            terminationReason: state.terminationReason,
            showFullscreenModal: state.showFullscreenModal,
            examCanStart: state.examCanStart,
        }))
    );

    const translations = useMemo(() => ({
        title: trans('student_pages.take.title', { exam: exam.title }),
        examTerminatedTitle: trans('student_pages.take.exam_terminated_title'),
        examAlreadySubmitted: trans('student_pages.take.exam_already_submitted'),
        noQuestionsTitle: trans('student_pages.take.no_questions_title'),
        noQuestionsSubtitle: trans('student_pages.take.no_questions_subtitle'),
        noQuestionsMessage: trans('student_pages.take.no_questions_message'),
        timeRemaining: trans('student_pages.take.time_remaining'),
        fullscreenRequired: trans('student_pages.take.fullscreen_required'),
        submitting: trans('student_pages.take.submitting'),
        finishExam: trans('student_pages.take.finish_exam'),
        importantInstructions: trans('student_pages.take.important_instructions'),
        warningTitle: trans('student_pages.take.warning_title'),
        warningMessage1: trans('student_pages.take.warning_message_1'),
        warningMessage2: trans('student_pages.take.warning_message_2'),
        warningMessage3: trans('student_pages.take.warning_message_3'),
        warningAutoSave: trans('student_pages.take.warning_auto_save'),
        fullscreenActivationTitle: trans('student_pages.take.fullscreen_activation_title'),
        attention: trans('student_pages.take.attention'),
        fullscreenActivationMessage: trans('student_pages.take.fullscreen_activation_message'),
        confirmSubmitTitle: trans('student_pages.take.confirm_submit_title'),
        confirmSubmitMessage: trans('student_pages.take.confirm_submit_message'),
        confirmSubmitCheck: trans('student_pages.take.confirm_submit_check'),
    }), [exam.title]);

    const {
        security,
        processing,
        handleAnswerChange,
        handleSubmit,
        enterFullscreen,
    } = useTakeExam({ exam, questions, userAnswers });

    if (examTerminated) {
        return (
            <AlertSecurityViolation
                exam={exam}
                reason={terminationReason || "Violation de sécurité détectée"}
            />
        );
    }

    if (assignment.submitted_at) {
        return (
            <CanNotTakeExam
                title={translations.examTerminatedTitle}
                message={translations.examAlreadySubmitted}
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }

    if (
        !questions || questions.length === 0
    ) {
        return (
            <CanNotTakeExam
                title={translations.noQuestionsTitle}
                subtitle={translations.noQuestionsSubtitle}
                message={translations.noQuestionsMessage}
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }


    return (
        <div className="bg-gray-50 min-h-screen">
            <Head title={translations.title} />

            <div className="bg-white py-4 border-b border-gray-200 fixed w-full z-1 top-0">
                <div className="container mx-auto flex justify-between items-center">
                    <TextEntry
                        className=' text-start'
                        label={exam.title}
                        value={exam.description ? (exam.description.length > 100 ? exam.description.substring(0, 100) + '...' : exam.description) : ''}
                    />

                    <TextEntry
                        className=' text-center'
                        label={translations.timeRemaining}
                        value={formatTime(timeLeft)}
                    />

                    {!security.isFullscreen && <TextEntry
                        className=' text-center'
                        label={translations.fullscreenRequired}
                        value=""
                    />}
                    <Button
                        size="sm"
                        color="primary"

                        onClick={() => setShowConfirmModal(true)}
                        disabled={isSubmitting || processing}
                        loading={isSubmitting || processing}

                    >
                        {isSubmitting || processing ? translations.submitting : translations.finishExam}
                    </Button>
                </div>
            </div>

            <div className="pt-20 max-w-6xl mx-auto">
                <div className="container mx-auto px-4 py-8">
                    <Section title={translations.importantInstructions} collapsible>
                        <AlertEntry type="warning" title={translations.warningTitle}>
                            <p>
                                {translations.warningMessage1}
                                <strong> {translations.warningMessage2}</strong> {translations.warningMessage3}
                            </p>
                            <p>
                                {translations.warningAutoSave}
                            </p>
                        </AlertEntry>
                    </Section>
                    {examCanStart && questions.length > 0 && (
                        questions.map((currentQ) => (
                            <TakeQuestion
                                key={currentQ.id}
                                question={currentQ}
                                answers={answers}
                                onAnswerChange={handleAnswerChange}
                            />
                        ))
                    )}

                    {!examCanStart && (
                        <Section title={translations.fullscreenActivationTitle} collapsible={false}>
                            <AlertEntry type="info" title={translations.attention}>
                                <p>
                                    {translations.fullscreenActivationMessage}
                                </p>
                            </AlertEntry>
                        </Section>
                    )}
                </div>
            </div>

            <ConfirmationModal
                title={translations.confirmSubmitTitle}
                message={translations.confirmSubmitMessage}
                icon={QuestionMarkCircleIcon}
                type='info'
                isOpen={showConfirmModal}
                onClose={() => setShowConfirmModal(false)}
                onConfirm={handleSubmit}
                loading={isSubmitting || processing}
            >
                <p className="text-gray-600 mb-6 text-center ">
                    {translations.confirmSubmitCheck}
                </p>
            </ConfirmationModal>

            <FullscreenModal
                isOpen={
                    showFullscreenModal
                }
                onEnterFullscreen={enterFullscreen}
            />
        </div>
    );
}


