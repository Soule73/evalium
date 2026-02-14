import { Head, router } from '@inertiajs/react';
import { useMemo, useState, useCallback, useEffect } from 'react';
import {
  AlertEntry,
  Button,
  ConfirmationModal,
  FileUploadZone,
  QuestionNavigation,
  Section,
  TakeQuestion,
  TextEntry,
} from '@/Components';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Answer, type Assessment, type AssessmentAssignment, type AssignmentAttachment, type Question } from '@/types';
import { QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import { useAssessmentAnswers, useAssessmentAnswerSave, useAssessmentSubmission, useQuestionNavigation } from '@/hooks/features/assessment';
import { formatDate, trans } from '@/utils';
import { breadcrumbs } from '@/utils/helpers/breadcrumbs';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { route } from 'ziggy-js';

interface WorkProps {
  assessment: Assessment;
  assignment: AssessmentAssignment;
  questions: Question[];
  userAnswers: Answer[];
  remainingSeconds: number | null;
  attachments?: AssignmentAttachment[];
}

function Work({ assessment, assignment, questions = [], userAnswers = [], attachments: initialAttachments = [] }: WorkProps) {
  const [savingStatus, setSavingStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [fileAttachments, setFileAttachments] = useState<AssignmentAttachment[]>(initialAttachments);

  const handleAttachmentAdded = useCallback((attachment: AssignmentAttachment) => {
    setFileAttachments((prev) => [attachment, ...prev]);
  }, []);

  const handleAttachmentRemoved = useCallback((attachmentId: number) => {
    setFileAttachments((prev) => prev.filter((a) => a.id !== attachmentId));
  }, []);

  const {
    answers,
    showConfirmModal,
    setShowConfirmModal,
    isSubmitting,
    shuffledQuestionIds,
  } = useAssessmentTakeStore(
    useShallow((state) => ({
      answers: state.answers,
      showConfirmModal: state.showConfirmModal,
      setShowConfirmModal: state.setShowConfirmModal,
      isSubmitting: state.isSubmitting,
      shuffledQuestionIds: state.shuffledQuestionIds,
    }))
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
  });

  const { updateAnswer } = useAssessmentAnswers({ questions, userAnswers });
  const { saveAnswerIndividual, forceSave, cleanup } = useAssessmentAnswerSave({ assessmentId: assessment.id });

  const { processing, handleSubmit: submitAssessment } = useAssessmentSubmission({
    assessmentId: assessment.id,
  });

  useEffect(() => {
    return cleanup;
  }, [cleanup]);

  const answeredQuestionIds = useMemo(() => {
    return new Set(Object.keys(answers).map(Number).filter((id) => {
      const answer = answers[id];
      if (Array.isArray(answer)) return answer.length > 0;
      return answer !== undefined && answer !== '' && answer !== null;
    }));
  }, [answers]);

  const handleAnswerChange = useCallback((questionId: number, value: string | number | number[]) => {
    updateAnswer(questionId, value);
    const updatedAnswers = { ...useAssessmentTakeStore.getState().answers, [questionId]: value };
    saveAnswerIndividual(questionId, value, updatedAnswers);
  }, [updateAnswer, saveAnswerIndividual]);

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

  const translations = useMemo(() => ({
    title: trans('student_assessment_pages.work.title', { assessment: assessment.title }),
    submitWork: trans('student_assessment_pages.work.submit_work'),
    submitting: trans('student_assessment_pages.work.submitting'),
    saveProgress: trans('student_assessment_pages.work.save_progress'),
    saving: trans('student_assessment_pages.work.saving'),
    saved: trans('student_assessment_pages.work.saved'),
    saveError: trans('student_assessment_pages.work.save_error'),
    dueDateLabel: trans('student_assessment_pages.work.due_date_label'),
    autoSaveNotice: trans('student_assessment_pages.work.auto_save_notice'),
    homeworkInstructions: trans('student_assessment_pages.work.homework_instructions'),
    instructionMultiSession: trans('student_assessment_pages.work.instruction_multi_session'),
    instructionAutoSave: trans('student_assessment_pages.work.instruction_auto_save'),
    instructionDueDate: trans('student_assessment_pages.work.instruction_due_date'),
    confirmSubmitTitle: trans('student_assessment_pages.work.confirm_submit_title'),
    confirmSubmitMessage: trans('student_assessment_pages.work.confirm_submit_message'),
    confirmSubmitCheck: trans('student_assessment_pages.work.confirm_submit_check'),
    noQuestionsTitle: trans('student_assessment_pages.work.no_questions_title'),
    noQuestionsMessage: trans('student_assessment_pages.work.no_questions_message'),
    modalConfirmText: trans('components.confirmation_modal.confirm'),
    modalCancelText: trans('components.confirmation_modal.cancel'),
  }), [assessment.title]);

  const saveButtonLabel = useMemo(() => {
    switch (savingStatus) {
      case 'saving': return translations.saving;
      case 'saved': return translations.saved;
      case 'error': return translations.saveError;
      default: return translations.saveProgress;
    }
  }, [savingStatus, translations]);

  if (assignment.submitted_at) {
    return (
      <AuthenticatedLayout title={assessment.title} breadcrumb={breadcrumbs.student.assessmentWork(assessment)}>
        <Section title={assessment.title}>
          <AlertEntry type="info" title={trans('student_assessment_pages.take.assessment_terminated_title')}>
            <p>{trans('student_assessment_pages.take.assessment_already_submitted')}</p>
          </AlertEntry>
          <div className="mt-4">
            <Button
              size="sm"
              color="secondary"
              variant="outline"
              onClick={() => router.visit(route('student.assessments.index'))}
            >
              {trans('student_assessment_pages.results.back_to_assessments')}
            </Button>
          </div>
        </Section>
      </AuthenticatedLayout>
    );
  }

  if (!questions || questions.length === 0) {
    return (
      <AuthenticatedLayout title={assessment.title} breadcrumb={breadcrumbs.student.assessmentWork(assessment)}>
        <Section title={assessment.title}>
          <AlertEntry type="warning" title={translations.noQuestionsTitle}>
            <p>{translations.noQuestionsMessage}</p>
          </AlertEntry>
        </Section>
      </AuthenticatedLayout>
    );
  }

  return (
    <AuthenticatedLayout title={assessment.title} breadcrumb={breadcrumbs.student.assessmentWork(assessment)}>
      <Head title={translations.title} />

      <div className="space-y-6">
        <Section
          title={assessment.title}
          actions={
            <div className="flex items-center space-x-3">
              <Button
                size="sm"
                color="secondary"
                variant="outline"
                onClick={handleManualSave}
                disabled={savingStatus === 'saving'}
                loading={savingStatus === 'saving'}
              >
                {saveButtonLabel}
              </Button>
              <Button
                size="sm"
                color="primary"
                onClick={() => setShowConfirmModal(true)}
                disabled={isSubmitting || processing}
                loading={isSubmitting || processing}
              >
                {isSubmitting || processing ? translations.submitting : translations.submitWork}
              </Button>
            </div>
          }
        >
          <div className="space-y-4">
            {assessment.description && (
              <p className="text-gray-600">{assessment.description}</p>
            )}

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <TextEntry
                label={trans('student_assessment_pages.show.subject')}
                value={assessment.class_subject?.subject?.name || '-'}
              />
              <TextEntry
                label={trans('student_assessment_pages.show.teacher')}
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
                {assessment.due_date && (
                  <li>{translations.instructionDueDate}</li>
                )}
              </ul>
            </AlertEntry>

            {(assessment.max_files ?? 0) > 0 && (
              <FileUploadZone
                assessmentId={assessment.id}
                attachments={fileAttachments}
                maxFiles={assessment.max_files ?? 0}
                maxFileSize={assessment.max_file_size ?? 10240}
                allowedExtensions={assessment.allowed_extensions ?? null}
                onAttachmentAdded={handleAttachmentAdded}
                onAttachmentRemoved={handleAttachmentRemoved}
                disabled={!!assignment.submitted_at}
              />
            )}
          </div>
        </Section>

        {displayedQuestions.map((currentQ) => (
          <TakeQuestion
            key={currentQ.id}
            question={currentQ}
            answers={answers}
            onAnswerChange={handleAnswerChange}
          />
        ))}

        {oneQuestionPerPage && displayedQuestions.length > 0 && (
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

        <div className="flex justify-end space-x-3 pb-8">
          <Button
            size="sm"
            color="secondary"
            variant="outline"
            onClick={handleManualSave}
            disabled={savingStatus === 'saving'}
            loading={savingStatus === 'saving'}
          >
            {saveButtonLabel}
          </Button>
          <Button
            size="sm"
            color="primary"
            onClick={() => setShowConfirmModal(true)}
            disabled={isSubmitting || processing}
            loading={isSubmitting || processing}
          >
            {isSubmitting || processing ? translations.submitting : translations.submitWork}
          </Button>
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
