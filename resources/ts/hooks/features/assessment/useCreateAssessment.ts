import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useShallow } from 'zustand/react/shallow';
import { QuestionFormData, AssessmentType } from '@/types';
import { useAssessmentFormStore } from '@/stores/useAssessmentFormStore';

interface AssessmentCreateData {
  title: string;
  description: string;
  duration: number;
  scheduled_date: string;
  type: AssessmentType;
  class_subject_id: number;
  is_published: boolean;
  shuffle_questions: boolean;
  show_results_immediately: boolean;
  allow_late_submission: boolean;
  one_question_per_page: boolean;
  questions: QuestionFormData[];
}

export const useCreateAssessment = () => {
  const { questions, resetStore } = useAssessmentFormStore(useShallow((state) => ({
    questions: state.questions,
    resetStore: state.reset,
  })));

  const { data, setData, post, processing, errors, reset, clearErrors, transform } = useForm<AssessmentCreateData>({
    title: '',
    description: '',
    duration: 60,
    scheduled_date: '',
    type: 'devoir',
    class_subject_id: 0,
    is_published: false,
    shuffle_questions: false,
    show_results_immediately: true,
    allow_late_submission: false,
    one_question_per_page: false,
    questions: []
  });

  useEffect(() => {
    resetStore();
  }, [resetStore]);

  useEffect(() => {
    if (Object.keys(errors).length > 0) {
      console.log('Validation errors detected:', errors);
    }
  }, [errors]);

  const handleFieldChange = (field: string, value: string | number | boolean) => {
    setData(field as keyof AssessmentCreateData, value);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    clearErrors();

    console.log('Submitted data:', { ...data, questions });

    transform((data) => ({
      ...data,
      questions
    }));

    post(route('teacher.assessments.store'), {
      onSuccess: () => {
        reset();
        resetStore();
      }
    });
  };

  return {
    data,
    errors,
    processing,
    handleFieldChange,
    handleSubmit,
    reset
  };
};
