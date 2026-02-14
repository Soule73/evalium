import { useState, useCallback, useRef, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

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

  const { isSubmitting, showConfirmModal, setIsSubmitting, setShowConfirmModal } = useAssessmentTakeStore(useShallow((state) => ({
    isSubmitting: state.isSubmitting,
    showConfirmModal: state.showConfirmModal,
    setIsSubmitting: state.setIsSubmitting,
    setShowConfirmModal: state.setShowConfirmModal,
  })));

  const onSubmitSuccessRef = useRef(onSubmitSuccess);
  const onSubmitErrorRef = useRef(onSubmitError);

  useEffect(() => {
    onSubmitSuccessRef.current = onSubmitSuccess;
    onSubmitErrorRef.current = onSubmitError;
  }, [onSubmitSuccess, onSubmitError]);

  const handleSubmit = useCallback(
    (answers: Record<number, string | number | number[]>) => {
      setIsSubmitting(true);
      setProcessing(true);
      setShowConfirmModal(false);

      router.post(
        route('student.assessments.submit', assessmentId),
        { answers },
        {
          preserveScroll: true,
          onSuccess: () => {
            setIsSubmitting(false);
            setProcessing(false);
            onSubmitSuccessRef.current?.();
          },
          onError: () => {
            setIsSubmitting(false);
            setProcessing(false);
            onSubmitErrorRef.current?.();
          },
        }
      );
    },
    [assessmentId, setIsSubmitting, setProcessing, setShowConfirmModal]
  );

  return {
    isSubmitting,
    showConfirmModal,
    setShowConfirmModal,
    processing,
    handleSubmit,
  };
};
