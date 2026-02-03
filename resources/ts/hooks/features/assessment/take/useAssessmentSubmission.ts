import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';

interface UseAssessmentSubmissionParams {
  assessmentId: number;
  onSubmitSuccess?: () => void;
  onSubmitError?: () => void;
}

/**
 * Custom hook to handle assessment submission logic.
 */
export const useAssessmentSubmission = ({
  assessmentId,
  onSubmitSuccess,
  onSubmitError,
}: UseAssessmentSubmissionParams) => {
  const [processing, setProcessing] = useState(false);

  const { isSubmitting, showConfirmModal, setIsSubmitting, setShowConfirmModal } = useAssessmentTakeStore((state) => ({
    isSubmitting: state.isSubmitting,
    showConfirmModal: state.showConfirmModal,
    setIsSubmitting: state.setIsSubmitting,
    setShowConfirmModal: state.setShowConfirmModal,
  }));

  const handleSubmit = useCallback(
    (answers: Record<number, string | number | number[]>) => {
      setIsSubmitting(true);
      setProcessing(true);
      setShowConfirmModal(false);

      router.post(
        route('student.mcd.assessments.submit', assessmentId),
        { answers },
        {
          preserveScroll: true,
          onSuccess: () => {
            setIsSubmitting(false);
            setProcessing(false);
            onSubmitSuccess?.();
          },
          onError: () => {
            setIsSubmitting(false);
            setProcessing(false);
            onSubmitError?.();
          },
        }
      );
    },
    [assessmentId, setIsSubmitting, setShowConfirmModal, onSubmitSuccess, onSubmitError]
  );

  return {
    isSubmitting,
    showConfirmModal,
    setShowConfirmModal,
    processing,
    handleSubmit,
  };
};
