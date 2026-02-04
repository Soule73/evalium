import { useEffect, useCallback } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useShallow } from 'zustand/react/shallow';
import { QuestionFormData, Assessment, AssessmentType } from '@/types';
import { useAssessmentFormStore } from '@/stores/useAssessmentFormStore';

interface AssessmentEditData {
  title: string;
  description: string;
  duration: number;
  scheduled_date: string;
  type: AssessmentType;
  class_subject_id: number;
  is_published: boolean;
  questions: QuestionFormData[];
  deletedQuestionIds: number[];
  deletedChoiceIds: number[];
}

interface UseEditAssessmentReturn {
  data: AssessmentEditData;
  errors: Record<string, string>;
  processing: boolean;
  handleFieldChange: (field: string, value: string | number | boolean) => void;
  handleSubmit: (e: React.FormEvent) => void;
}

export const useEditAssessment = (assessment: Assessment): UseEditAssessmentReturn => {
  const {
    questions,
    setQuestions,
    deletedQuestionIds,
    deletedChoiceIds,
    clearDeletedHistory,
    resetStore,
  } = useAssessmentFormStore(useShallow((state) => ({
    questions: state.questions,
    setQuestions: state.setQuestions,
    deletedQuestionIds: state.deletedQuestionIds,
    deletedChoiceIds: state.deletedChoiceIds,
    clearDeletedHistory: state.clearDeletedHistory,
    resetStore: state.reset,
  })));

  const { data, setData, put, processing, errors, clearErrors, transform } = useForm<AssessmentEditData>({
    title: assessment.title || '',
    description: assessment.description || '',
    duration: assessment.duration_minutes || 60,
    scheduled_date: assessment.scheduled_at ? assessment.scheduled_at.slice(0, 16) : '',
    type: assessment.type,
    class_subject_id: assessment.class_subject_id || 0,
    is_published: assessment.is_published ?? false,
    questions: [],
    deletedQuestionIds: [],
    deletedChoiceIds: []
  });

  useEffect(() => {
    if (assessment.questions && questions.length === 0) {
      const formattedQuestions: QuestionFormData[] = assessment.questions.map((q, index) => ({
        id: q.id,
        content: q.content,
        type: q.type,
        points: q.points,
        order_index: q.order_index || index,
        choices: q.choices?.map((c, choiceIndex) => {
          let content = c.content;
          if (q.type === 'boolean') {
            if (content !== 'true' && content !== 'false') {
              content = choiceIndex === 0 ? 'true' : 'false';
            }
          }
          return {
            id: c.id,
            content,
            is_correct: c.is_correct,
            order_index: c.order_index || choiceIndex
          };
        }) || []
      }));
      setQuestions(formattedQuestions);
      setData('questions', formattedQuestions);
    }
  }, [assessment.questions, questions.length, setQuestions, setData]);

  useEffect(() => {
    return () => {
      resetStore();
    };
  }, [resetStore]);

  useEffect(() => {
    if (Object.keys(errors).length > 0) {
      console.log('Validation errors detected:', errors);
    }
  }, [errors]);

  const handleFieldChange = useCallback((field: string, value: string | number | boolean) => {
    setData(field as keyof AssessmentEditData, value);
  }, [setData]);

  const handleSubmit = useCallback((e: React.FormEvent) => {
    e.preventDefault();

    clearErrors();

    transform((data) => ({
      ...data,
      questions,
      deletedQuestionIds,
      deletedChoiceIds
    }));

    put(route('teacher.assessments.update', assessment.id), {
      onSuccess: () => {
        clearDeletedHistory();
      },
      onError: (errors: any) => {
        console.error('Submission errors:', errors);
      }
    });
  }, [questions, deletedQuestionIds, deletedChoiceIds, assessment.id, clearDeletedHistory, clearErrors, transform, put]);

  return {
    data,
    errors,
    processing,
    handleFieldChange,
    handleSubmit
  };
};
