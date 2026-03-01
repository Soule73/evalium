import { Head, router } from '@inertiajs/react';
import { useMemo, useState, useCallback, useEffect } from 'react';
import { AlertEntry, Button, ConfirmationModal, Section, TextEntry } from '@/Components';
import { QuestionProvider, QuestionCard } from '@/Components/features/assessment/question';
import { WorkSubmitActions, getSaveButtonLabel } from '@/Components/features/assessment';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Answer, type Assessment, type AssessmentAssignment, type Question } from '@/types';
import { QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import {
    useAssessmentAnswers,
    useAssessmentAnswerSave,
    useAssessmentSubmission,
    useQuestionNavigation,
} from '@/hooks/features/assessment';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatDate } from '@/utils';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { route } from 'ziggy-js';

interface WorkProps {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    questions: Question[];
    userAnswers: Answer[];
    remainingSeconds: number | null;
    fileAnswers?: Answer[];
}

function Work({
    assessment,
    assignment,
    questions = [],
    userAnswers = [],
    fileAnswers: initialFileAnswers = [],
}: WorkProps) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const [savingStatus, setSavingStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
    const [fileAnswersByQuestion, setFileAnswersByQuestion] = useState<Record<number, Answer>>(() =>
        Object.fromEntries(initialFileAnswers.map((a) => [a.question_id, a])),
    );

    const handleFileAnswerSaved = useCallback((questionId: number, answer: Answer) => {
        setFileAnswersByQuestion((prev) => ({ ...prev, [questionId]: answer }));
    }, []);

    const handleFileAnswerRemoved = useCallback((questionId: number) => {
        setFileAnswersByQuestion((prev) => {
            const next = { ...prev };
            delete next[questionId];
            return next;
        });
    }, []);

    const { answers, showConfirmModal, setShowConfirmModal, isSubmitting } = useAssessmentTakeStore(
        useShallow((state) => ({
            answers: state.answers,
            showConfirmModal: state.showConfirmModal,
            setShowConfirmModal: state.setShowConfirmModal,
            isSubmitting: state.isSubmitting,
        })),
    );

    const { displayedQuestions } = useQuestionNavigation({
        questions,
        shuffleEnabled: assessment.shuffle_questions ?? false,
    });

    const { updateAnswer } = useAssessmentAnswers({ questions, userAnswers });
    const { saveAnswerIndividual, forceSave, cleanup } = useAssessmentAnswerSave({
        assessmentId: assessment.id,
    });

    const { processing, handleSubmit: submitAssessment } = useAssessmentSubmission({
        assessmentId: assessment.id,
    });

    useEffect(() => {
        return cleanup;
    }, [cleanup]);

    const handleAnswerChange = useCallback(
        (questionId: number, value: string | number | number[]) => {
            updateAnswer(questionId, value);
            const updatedAnswers = {
                ...useAssessmentTakeStore.getState().answers,
                [questionId]: value,
            };
            saveAnswerIndividual(questionId, value, updatedAnswers);
        },
        [updateAnswer, saveAnswerIndividual],
    );

    const handleManualSave = useCallback(async () => {
        setSavingStatus('saving');
        try {
            await forceSave(useAssessmentTakeStore.getState().answers);
            setSavingStatus('saved');
            setTimeout(() => setSavingStatus('idle'), 2000);
        } catch {
            setSavingStatus('error');
            setTimeout(() => setSavingStatus('idle'), 3000);
        }
    }, [forceSave]);

    const handleSubmit = useCallback(() => {
        submitAssessment(useAssessmentTakeStore.getState().answers);
    }, [submitAssessment]);

    const translations = useMemo(
        () => ({
            submitWork: t('student_assessment_pages.work.submit_work'),
            submitting: t('student_assessment_pages.work.submitting'),
            saveProgress: t('student_assessment_pages.work.save_progress'),
            saving: t('student_assessment_pages.work.saving'),
            saved: t('student_assessment_pages.work.saved'),
            saveError: t('student_assessment_pages.work.save_error'),
            dueDateLabel: t('student_assessment_pages.work.due_date_label'),
            autoSaveNotice: t('student_assessment_pages.work.auto_save_notice'),
            homeworkInstructions: t('student_assessment_pages.work.homework_instructions'),
            instructionMultiSession: t('student_assessment_pages.work.instruction_multi_session'),
            instructionAutoSave: t('student_assessment_pages.work.instruction_auto_save'),
            instructionDueDate: t('student_assessment_pages.work.instruction_due_date'),
            confirmSubmitTitle: t('student_assessment_pages.work.confirm_submit_title'),
            confirmSubmitMessage: t('student_assessment_pages.work.confirm_submit_message'),
            confirmSubmitCheck: t('student_assessment_pages.work.confirm_submit_check'),
            noQuestionsTitle: t('student_assessment_pages.work.no_questions_title'),
            noQuestionsMessage: t('student_assessment_pages.work.no_questions_message'),
            modalConfirmText: t('commons/ui.confirm'),
            modalCancelText: t('commons/ui.cancel'),
        }),
        [t],
    );

    const titleTranslation = useMemo(
        () => t('student_assessment_pages.work.title', { assessment: assessment.title }),
        [t, assessment.title],
    );

    const saveButtonLabel = getSaveButtonLabel(savingStatus, {
        idle: translations.saveProgress,
        saving: translations.saving,
        saved: translations.saved,
        error: translations.saveError,
    });

    if (assignment.submitted_at) {
        return (
            <AuthenticatedLayout
                title={assessment.title}
                breadcrumb={breadcrumbs.student.assessmentWork(assessment)}
            >
                <Section title={assessment.title}>
                    <AlertEntry
                        type="info"
                        title={t('student_assessment_pages.take.assessment_terminated_title')}
                    >
                        <p>{t('student_assessment_pages.take.assessment_already_submitted')}</p>
                    </AlertEntry>
                    <div className="mt-4">
                        <Button
                            size="sm"
                            color="secondary"
                            variant="outline"
                            onClick={() => router.visit(route('student.assessments.index'))}
                        >
                            {t('student_assessment_pages.results.back_to_assessments')}
                        </Button>
                    </div>
                </Section>
            </AuthenticatedLayout>
        );
    }

    if (!questions || questions.length === 0) {
        return (
            <AuthenticatedLayout
                title={assessment.title}
                breadcrumb={breadcrumbs.student.assessmentWork(assessment)}
            >
                <Section title={assessment.title}>
                    <AlertEntry type="warning" title={translations.noQuestionsTitle}>
                        <p>{translations.noQuestionsMessage}</p>
                    </AlertEntry>
                </Section>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout
            title={assessment.title}
            breadcrumb={breadcrumbs.student.assessmentWork(assessment)}
        >
            <Head title={titleTranslation} />

            <div className="space-y-6">
                <Section
                    title={assessment.title}
                    actions={
                        <WorkSubmitActions
                            savingStatus={savingStatus}
                            isSubmitting={isSubmitting}
                            processing={processing}
                            saveButtonLabel={saveButtonLabel}
                            submitLabel={translations.submitWork}
                            submittingLabel={translations.submitting}
                            onSave={handleManualSave}
                            onSubmit={() => setShowConfirmModal(true)}
                        />
                    }
                >
                    <div className="space-y-4">
                        {assessment.description && (
                            <p className="text-gray-600">{assessment.description}</p>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <TextEntry
                                label={t('student_assessment_pages.show.subject')}
                                value={assessment.class_subject?.subject?.name || '-'}
                            />
                            <TextEntry
                                label={t('student_assessment_pages.show.teacher')}
                                value={assessment.class_subject?.teacher?.name || '-'}
                            />
                            {assessment.due_date && (
                                <TextEntry
                                    label={translations.dueDateLabel}
                                    value={formatDate(assessment.due_date, 'datetime')}
                                />
                            )}
                        </div>

                        <AlertEntry type="info" title={translations.homeworkInstructions}>
                            <ul className="list-disc list-inside space-y-1 text-sm">
                                <li>{translations.instructionMultiSession}</li>
                                <li>{translations.instructionAutoSave}</li>
                                {assessment.due_date && <li>{translations.instructionDueDate}</li>}
                            </ul>
                        </AlertEntry>
                    </div>
                </Section>

                {displayedQuestions.map((currentQ, index) => (
                    <QuestionProvider
                        key={currentQ.id}
                        mode="take"
                        role="student"
                        isDisabled={!!assignment.submitted_at}
                        assessmentId={assessment.id}
                        answers={answers}
                        onAnswerChange={handleAnswerChange}
                        fileAnswers={fileAnswersByQuestion}
                        onFileAnswerSaved={handleFileAnswerSaved}
                        onFileAnswerRemoved={handleFileAnswerRemoved}
                    >
                        <QuestionCard question={currentQ} questionIndex={index} />
                    </QuestionProvider>
                ))}

                <div className="flex justify-end pb-8">
                    <WorkSubmitActions
                        savingStatus={savingStatus}
                        isSubmitting={isSubmitting}
                        processing={processing}
                        saveButtonLabel={saveButtonLabel}
                        submitLabel={translations.submitWork}
                        submittingLabel={translations.submitting}
                        onSave={handleManualSave}
                        onSubmit={() => setShowConfirmModal(true)}
                    />
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
                <p className="text-gray-600 mb-6 text-center">{translations.confirmSubmitCheck}</p>
            </ConfirmationModal>
        </AuthenticatedLayout>
    );
}

export default Work;
