import { useEffect, useCallback, useRef } from 'react';
import { type Answer, type Assessment, type Question } from '@/types';
import { useAssessmentSecurity } from './useAssessmentSecurity';
import { useAssessmentTimer } from './useAssessmentTimer';
import { useAssessmentAnswers } from './useAssessmentAnswers';
import { useAssessmentSecurityViolation } from './useAssessmentSecurityViolation';
import { useAssessmentAnswerSave } from './useAssessmentAnswerSave';
import { useAssessmentSubmission } from './useAssessmentSubmission';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';
import { useAssessmentFullscreen } from './useAssessmentFullscreen';

interface UseTakeAssessment {
  assessment: Assessment;
  questions: Question[];
  userAnswers: Answer[];
  remainingSeconds: number | null;
}

/**
 * Custom React hook to manage the process of taking an assessment.
 *
 * This hook encapsulates the logic for handling assessment answers, submission, security,
 * fullscreen requirements, timing, auto-saving, and cleanup. It coordinates multiple
 * sub-hooks to provide a unified interface for the assessment-taking experience.
 *
 * @param params - An object containing:
 *   @param assessment - The assessment object containing assessment details.
 *   @param questions - An array of questions for the assessment.
 *   @param userAnswers - An array of the user's existing answers (optional).
 *
 * @returns An object with the following properties and handlers:
 * - `answers`: The current state of the user's answers.
 * - `isSubmitting`: Boolean indicating if the assessment is being submitted.
 * - `showConfirmModal`: Boolean to control the visibility of the confirmation modal.
 * - `setShowConfirmModal`: Function to set the confirmation modal visibility.
 * - `timeLeft`: Remaining time for the assessment.
 * - `security`: Security-related handlers and state.
 * - `processing`: Boolean indicating if a process (e.g., submission) is ongoing.
 * - `handleAnswerChange`: Function to update an answer for a question.
 * - `handleSubmit`: Function to submit the assessment.
 * - `autoSave`: Auto-save handler and state.
 * - `assessmentTerminated`: Boolean indicating if the assessment was terminated due to a violation.
 * - `terminationReason`: Reason for assessment termination, if any.
 * - `showFullscreenModal`: Boolean to control the fullscreen modal visibility.
 * - `fullscreenRequired`: Boolean indicating if fullscreen is required.
 * - `enterFullscreen`: Function to enter fullscreen mode.
 * - `assessmentCanStart`: Boolean indicating if the assessment can be started.
 */
const useTakeAssessment = ({ assessment, questions = [], userAnswers = [], remainingSeconds }: UseTakeAssessment) => {
  const { answers } = useAssessmentTakeStore(
    useShallow((state) => ({
      answers: state.answers,
    }))
  );

  const answersRef = useRef(answers);

  useEffect(() => {
    answersRef.current = answers;
  }, [answers]);

  const { updateAnswer } = useAssessmentAnswers({ questions, userAnswers });

  const {
    isSubmitting,
    showConfirmModal,
    setShowConfirmModal,
    processing,
    handleSubmit: submitAssessment,
  } = useAssessmentSubmission({
    assessmentId: assessment.id,
    onSubmitSuccess: () => {
      exitFullscreenRef.current();
    },
    onSubmitError: () => {
      exitFullscreenRef.current();
    },
  });

  const {
    assessmentTerminated: assessmentTerminated,
    terminationReason,
    handleViolation,
  } = useAssessmentSecurityViolation({
    assessmentId: assessment.id,
  });

  const handleViolationCallback = useCallback(
    (type: string) => {
      handleViolation(type, answersRef.current);
    },
    [handleViolation]
  );

  const security = useAssessmentSecurity({
    onViolation: handleViolationCallback,
  });

  const { showFullscreenModal, fullscreenRequired, assessmentCanStart: assessmentCanStart, enterFullscreen, exitFullscreen } = useAssessmentFullscreen({ security });

  const exitFullscreenRef = useRef(exitFullscreen);

  useEffect(() => {
    exitFullscreenRef.current = exitFullscreen;
  }, [exitFullscreen]);

  const { saveAnswerIndividual, saveAllAnswers, forceSave, cleanup } = useAssessmentAnswerSave({
    assessmentId: assessment.id,
  });

  const handleSubmit = useCallback(() => {
    forceSave(answersRef.current).then(() => {
      submitAssessment(answersRef.current);
    });
  }, [forceSave, submitAssessment]);

  const { timeLeft } = useAssessmentTimer({
    remainingSeconds,
    onTimeEnd: handleSubmit,
    isSubmitting,
  });

  useEffect(() => {
    const autoSaveInterval = setInterval(() => {
      saveAllAnswers(answersRef.current);
    }, 30000);

    return () => clearInterval(autoSaveInterval);
  }, [saveAllAnswers]);

  useEffect(() => {
    if (assessmentTerminated) {
      exitFullscreenRef.current();
    }
  }, [assessmentTerminated]);

  const handleAnswerChange = useCallback(
    (questionId: number, value: string | number | number[]) => {
      updateAnswer(questionId, value);

      const newAnswers = { ...answersRef.current, [questionId]: value };

      saveAnswerIndividual(questionId, value, newAnswers);
    },
    [updateAnswer, saveAnswerIndividual]
  );

  useEffect(() => {
    return () => {
      cleanup();
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return {
    answers,
    isSubmitting,
    showConfirmModal,
    setShowConfirmModal,
    timeLeft,
    security,
    processing,
    handleAnswerChange,
    handleSubmit,
    assessmentTerminated,
    terminationReason,
    showFullscreenModal,
    fullscreenRequired,
    enterFullscreen,
    assessmentCanStart,
  };
};

export default useTakeAssessment;
