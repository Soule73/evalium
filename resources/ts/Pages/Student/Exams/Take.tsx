import { Head } from '@inertiajs/react';
import { Button } from '@/Components';
import TextEntry from '@/Components/TextEntry';
import { Answer, Exam, ExamAssignment, Question } from '@/types';
import { ExclamationCircleIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import TakeQuestion from '@/Components/exam/TakeQuestion';
import useTakeExam from '@/hooks/exam/useTakeExam';
import AlertSecurityViolation, { CanNotTakeExam } from '@/Components/exam/AlertSecurityViolation';
import AlertEntry from '@/Components/AlertEntry';
import Section from '@/Components/Section';
import { formatTime } from '@/utils';
import ConfirmationModal from '@/Components/ConfirmationModal';
import FullscreenModal from '@/Components/exam/FullscreenModal';
import { trans } from '@/utils/translations';

interface TakeExamProps {
    exam: Exam;
    assignment: ExamAssignment;
    questions: Question[];
    userAnswers: Answer[];
}

export default function Take({ exam, assignment, questions = [], userAnswers = [] }: TakeExamProps) {

    const {
        answers,
        isSubmitting,
        showConfirmModal,
        setShowConfirmModal,
        timeLeft,
        security,
        processing,
        handleAnswerChange,
        handleSubmit,
        examTerminated,
        terminationReason,
        showFullscreenModal,
        enterFullscreen,
        examCanStart
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
                title={trans('student_pages.take.exam_terminated_title')}
                message={trans('student_pages.take.exam_already_submitted')}
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }

    if (
        !questions || questions.length === 0
    ) {
        return (
            <CanNotTakeExam
                title={trans('student_pages.take.no_questions_title')}
                subtitle={trans('student_pages.take.no_questions_subtitle')}
                message={trans('student_pages.take.no_questions_message')}
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }


    return (
        <div className="bg-gray-50 min-h-screen">
            <Head title={trans('student_pages.take.title', { exam: exam.title })} />

            <div className="bg-white py-4 border-b border-gray-200 fixed w-full z-1 top-0">
                <div className="container mx-auto flex justify-between items-center">
                    <TextEntry
                        className=' text-start'
                        label={exam.title}
                        value={exam.description ? (exam.description.length > 100 ? exam.description.substring(0, 100) + '...' : exam.description) : ''}
                    />

                    <TextEntry
                        className=' text-center'
                        label={trans('student_pages.take.time_remaining')}
                        value={formatTime(timeLeft)}
                    />

                    {!security.isFullscreen && <TextEntry
                        className=' text-center'
                        label={trans('student_pages.take.fullscreen_required')}
                        value=""
                    />}
                    <Button
                        size="sm"
                        color="primary"

                        onClick={() => setShowConfirmModal(true)}
                        disabled={isSubmitting || processing}
                        loading={isSubmitting || processing}

                    >
                        {isSubmitting || processing ? trans('student_pages.take.submitting') : trans('student_pages.take.finish_exam')}
                    </Button>
                </div>
            </div>

            <div className="pt-20 max-w-6xl mx-auto">
                <div className="container mx-auto px-4 py-8">
                    <Section title={trans('student_pages.take.important_instructions')} collapsible>
                        <AlertEntry type="warning" title={trans('student_pages.take.warning_title')}>
                            <p>
                                {trans('student_pages.take.warning_message_1')}
                                <strong> {trans('student_pages.take.warning_message_2')}</strong> {trans('student_pages.take.warning_message_3')}
                            </p>
                            <p>
                                {trans('student_pages.take.warning_auto_save')}
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
                        <Section title={trans('student_pages.take.fullscreen_activation_title')} collapsible={false}>
                            <AlertEntry type="info" title={trans('student_pages.take.attention')}>
                                <p>
                                    {trans('student_pages.take.fullscreen_activation_message')}
                                </p>
                            </AlertEntry>
                        </Section>
                    )}
                </div>
            </div>

            <ConfirmationModal
                title={trans('student_pages.take.confirm_submit_title')}
                message={trans('student_pages.take.confirm_submit_message')}
                icon={QuestionMarkCircleIcon}
                type='info'
                isOpen={showConfirmModal}
                onClose={() => setShowConfirmModal(false)}
                onConfirm={handleSubmit}
                loading={isSubmitting || processing}
            >
                <p className="text-gray-600 mb-6 text-center ">
                    {trans('student_pages.take.confirm_submit_check')}
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


