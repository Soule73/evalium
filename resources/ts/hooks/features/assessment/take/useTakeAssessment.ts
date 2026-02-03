import { useEffect, useCallback } from 'react';
import { Answer, Assessment, Question } from '@/types';
import { useExamSecurity } from '../../exam/take/useExamSecurity';
import { useAutoSave } from '../../exam/take/useAutoSave';
import { useAssessmentTimer } from './useAssessmentTimer';
import { useAssessmentAnswers } from './useAssessmentAnswers';
import { useExamFullscreen } from '../../exam/take/useExamFullscreen';
import { useExamSecurityViolation } from '../../exam/take/useExamSecurityViolation';
import { useAssessmentAnswerSave } from './useAssessmentAnswerSave';
import { useAssessmentSubmission } from './useAssessmentSubmission';
import { useAssessmentTakeStore } from '@/stores/useAssessmentTakeStore';
import { useShallow } from 'zustand/react/shallow';

interface UseTakeAssessment {
  assessment: Assessment;
  questions: Question[];
  userAnswers: Answer[];
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
const useTakeAssessment = ({ assessment, questions = [], userAnswers = [] }: UseTakeAssessment) => {
  const { answers } = useAssessmentTakeStore(
    useShallow((state) => ({
      answers: state.answers,
    }))
  );

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
      exitFullscreen();
    },
    onSubmitError: () => {
      exitFullscreen();
    },
  });

  const {
    examTerminated: assessmentTerminated,
    terminationReason,
    handleViolation,
  } = useExamSecurityViolation({
    examId: assessment.id,
  });

  const handleViolationCallback = useCallback(
    (type: string) => {
      handleViolation(type, answers);
    },
    [handleViolation, answers]
  );

  const security = useExamSecurity({
    onViolation: handleViolationCallback,
  });

  const { showFullscreenModal, fullscreenRequired, examCanStart: assessmentCanStart, enterFullscreen, exitFullscreen } = useExamFullscreen({ security });

  const handleSubmitCallback = useCallback(() => handleSubmit(), []);

  const { timeLeft } = useAssessmentTimer({
    duration: assessment.duration,
    onTimeEnd: handleSubmitCallback,
    isSubmitting,
  });

  const { saveAnswerIndividual, saveAllAnswers, forceSave, cleanup } = useAssessmentAnswerSave({
    assessmentId: assessment.id,
  });

  const handleAutoSave = useCallback(() => {
    return saveAllAnswers(answers);
  }, [saveAllAnswers, answers]);

  const autoSave = useAutoSave(answers, {
    interval: 30000,
    onSave: handleAutoSave,
    onError: () => { },
  });

  useEffect(() => {
    if (assessmentTerminated) {
      exitFullscreen();
    }
  }, [assessmentTerminated, exitFullscreen]);

  const handleAnswerChange = useCallback(
    (questionId: number, value: string | number | number[]) => {
      updateAnswer(questionId, value);

      const newAnswers = { ...answers, [questionId]: value };

      saveAnswerIndividual(questionId, value, newAnswers);
    },
    [updateAnswer, answers, saveAnswerIndividual]
  );

  const handleSubmit = useCallback(() => {
    forceSave(answers).then(() => {
      submitAssessment(answers);
    });
  }, [forceSave, answers, submitAssessment]);

  useEffect(() => {
    return cleanup;
  }, [cleanup]);

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
    autoSave,
    assessmentTerminated,
    terminationReason,
    showFullscreenModal,
    fullscreenRequired,
    enterFullscreen,
    assessmentCanStart,
  };
};

export default useTakeAssessment;
