import { router, useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { FormEventHandler, useState, useEffect } from 'react';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, Input, Select, Textarea, Section } from '@/Components';
import { ClassSubject, Assessment, AssessmentFormData, QuestionFormData, ChoiceFormData } from '@/types';
import { breadcrumbs, trans } from '@/utils';

interface Props {
  assessment: Assessment;
  classSubjects: ClassSubject[];
}

export default function Edit({ assessment, classSubjects }: Props) {
  const { data, setData, patch, processing, errors } = useForm<AssessmentFormData>({
    class_subject_id: assessment.class_subject_id,
    title: assessment.title,
    description: assessment.description || '',
    type: assessment.type,
    coefficient: assessment.coefficient,
    duration: assessment.duration,
    assessment_date: assessment.assessment_date,
    is_published: assessment.is_published,
    questions: assessment.questions?.map((q, i) => ({
      id: q.id,
      content: q.content,
      type: q.type,
      points: q.points,
      order_index: i,
      choices: q.choices?.map((c, j) => ({
        id: c.id,
        content: c.content,
        is_correct: c.is_correct,
        order_index: j,
      })) || [],
    })) || [],
    deletedQuestionIds: [],
    deletedChoiceIds: [],
  });

  const [expandedQuestions, setExpandedQuestions] = useState<Set<number>>(new Set());

  useEffect(() => {
    if (data.questions && data.questions.length > 0) {
      setExpandedQuestions(new Set([0]));
    }
  }, []);

  const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();
    patch(route('teacher.assessments.update', assessment.id));
  };

  const handleAddQuestion = () => {
    const newQuestion: QuestionFormData = {
      content: '',
      type: 'one_choice',
      points: 1,
      order_index: data.questions?.length || 0,
      choices: [],
    };
    const newLength = (data.questions?.length || 0);
    setData('questions', [...(data.questions || []), newQuestion]);
    setExpandedQuestions(new Set([newLength]));
  };

  const handleRemoveQuestion = (index: number) => {
    const questionToRemove = data.questions?.[index];
    const newDeletedQuestionIds = [...(data.deletedQuestionIds || [])];

    if (questionToRemove?.id) {
      newDeletedQuestionIds.push(questionToRemove.id);
    }

    const newQuestions = data.questions?.filter((_, i) => i !== index) || [];

    setData({
      ...data,
      questions: newQuestions.map((q, i) => ({ ...q, order_index: i })),
      deletedQuestionIds: newDeletedQuestionIds,
    });

    setExpandedQuestions(new Set(
      Array.from(expandedQuestions).filter(i => i !== index).map(i => i > index ? i - 1 : i)
    ));
  };

  const handleQuestionChange = (index: number, field: keyof QuestionFormData, value: any) => {
    const newQuestions = [...(data.questions || [])];
    newQuestions[index] = { ...newQuestions[index], [field]: value };
    setData('questions', newQuestions);
  };

  const handleAddChoice = (questionIndex: number) => {
    const newQuestions = [...(data.questions || [])];
    const question = newQuestions[questionIndex];
    const newChoice: ChoiceFormData = {
      content: '',
      is_correct: false,
      order_index: question.choices?.length || 0,
    };
    question.choices = [...(question.choices || []), newChoice];
    setData('questions', newQuestions);
  };

  const handleRemoveChoice = (questionIndex: number, choiceIndex: number) => {
    const newQuestions = [...(data.questions || [])];
    const question = newQuestions[questionIndex];
    const choiceToRemove = question.choices?.[choiceIndex];
    const newDeletedChoiceIds = [...(data.deletedChoiceIds || [])];

    if (choiceToRemove?.id) {
      newDeletedChoiceIds.push(choiceToRemove.id);
    }

    question.choices = (question.choices || [])
      .filter((_, i) => i !== choiceIndex)
      .map((c, i) => ({ ...c, order_index: i }));

    setData({
      ...data,
      questions: newQuestions,
      deletedChoiceIds: newDeletedChoiceIds,
    });
  };

  const handleChoiceChange = (
    questionIndex: number,
    choiceIndex: number,
    field: keyof ChoiceFormData,
    value: any
  ) => {
    const newQuestions = [...(data.questions || [])];
    const question = newQuestions[questionIndex];
    const newChoices = [...(question.choices || [])];
    newChoices[choiceIndex] = { ...newChoices[choiceIndex], [field]: value };
    question.choices = newChoices;
    setData('questions', newQuestions);
  };

  const toggleQuestionExpanded = (index: number) => {
    const newExpanded = new Set(expandedQuestions);
    if (newExpanded.has(index)) {
      newExpanded.delete(index);
    } else {
      newExpanded.add(index);
    }
    setExpandedQuestions(newExpanded);
  };

  const typeOptions = [
    { value: 'devoir', label: trans('teacher_pages.assessments.types.devoir') },
    { value: 'examen', label: trans('teacher_pages.assessments.types.examen') },
    { value: 'tp', label: trans('teacher_pages.assessments.types.tp') },
    { value: 'controle', label: trans('teacher_pages.assessments.types.controle') },
    { value: 'projet', label: trans('teacher_pages.assessments.types.projet') },
  ];

  const questionTypeOptions = [
    { value: 'one_choice', label: trans('teacher_pages.assessments.question_types.one_choice') },
    { value: 'multiple', label: trans('teacher_pages.assessments.question_types.multiple') },
    { value: 'text', label: trans('teacher_pages.assessments.question_types.text') },
    { value: 'boolean', label: trans('teacher_pages.assessments.question_types.boolean') },
  ];

  const classSubjectOptions = classSubjects.map(cs => ({
    value: cs.id,
    label: `${cs.class?.name} - ${cs.subject?.name}`,
  }));

  const coefficientOptions = Array.from({ length: 20 }, (_, i) => ({
    value: (i + 1) * 0.5,
    label: ((i + 1) * 0.5).toString(),
  }));

  return (
    <AuthenticatedLayout
      title={trans('teacher_pages.assessments.edit.title')}
      breadcrumb={breadcrumbs.editTeacherAssessment(assessment)}
    >
      <Section
        title={trans('teacher_pages.assessments.edit.heading')}
        subtitle={trans('teacher_pages.assessments.edit.description')}
      >
        <form onSubmit={handleSubmit} className="space-y-8">
          <div className="bg-white rounded-lg border border-gray-200 p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-6">
              {trans('teacher_pages.assessments.edit.basic_info')}
            </h3>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="md:col-span-2">
                <Select
                  label={trans('teacher_pages.assessments.form.class_subject')}
                  value={data.class_subject_id}
                  onChange={(value) => setData('class_subject_id', Number(value))}
                  options={classSubjectOptions}
                />
              </div>

              <div className="md:col-span-2">
                <Input
                  label={trans('teacher_pages.assessments.form.title')}
                  value={data.title}
                  onChange={(e) => setData('title', e.target.value)}
                  error={errors.title}
                  required
                />
              </div>

              <div className="md:col-span-2">
                <Textarea
                  label={trans('teacher_pages.assessments.form.description')}
                  value={data.description}
                  onChange={(e) => setData('description', e.target.value)}
                  error={errors.description}
                  rows={3}
                />
              </div>

              <Select
                label={trans('teacher_pages.assessments.form.type')}
                value={data.type}
                onChange={(value) => setData('type', value as any)}
                options={typeOptions}
                error={errors.type}
                required
              />

              <Select
                label={trans('teacher_pages.assessments.form.coefficient')}
                value={data.coefficient}
                onChange={(value) => setData('coefficient', Number(value))}
                options={coefficientOptions}
              />

              <Input
                type="number"
                label={trans('teacher_pages.assessments.form.duration')}
                value={data.duration}
                onChange={(e) => setData('duration', Number(e.target.value))}
                error={errors.duration}
                min={1}
                step={1}
                required
              />

              <Input
                type="date"
                label={trans('teacher_pages.assessments.form.assessment_date')}
                value={data.assessment_date}
                onChange={(e) => setData('assessment_date', e.target.value)}
                error={errors.assessment_date}
                required
              />

              <div className="md:col-span-2 flex items-center">
                <input
                  type="checkbox"
                  id="is_published"
                  checked={data.is_published}
                  onChange={(e) => setData('is_published', e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                />
                <label htmlFor="is_published" className="ml-2 text-sm text-gray-700">
                  {trans('teacher_pages.assessments.form.is_published')}
                </label>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg border border-gray-200 p-6">
            <div className="flex justify-between items-center mb-6">
              <h3 className="text-lg font-semibold text-gray-900">
                {trans('teacher_pages.assessments.edit.questions_section')}
              </h3>
              <Button
                type="button"
                onClick={handleAddQuestion}
                color="secondary"
                variant="outline"
                size="sm"
              >
                <PlusIcon className="w-4 h-4 mr-1" />
                {trans('teacher_pages.assessments.edit.add_question')}
              </Button>
            </div>

            {data.questions && data.questions.length === 0 && (
              <div className="text-center py-12 text-gray-500">
                <p className="mb-4">{trans('teacher_pages.assessments.edit.no_questions')}</p>
                <Button
                  type="button"
                  onClick={handleAddQuestion}
                  color="primary"
                  size="sm"
                >
                  <PlusIcon className="w-4 h-4 mr-1" />
                  {trans('teacher_pages.assessments.edit.add_first_question')}
                </Button>
              </div>
            )}

            <div className="space-y-4">
              {data.questions?.map((question, qIndex) => (
                <div key={qIndex} className="border border-gray-200 rounded-lg p-4">
                  <div className="flex justify-between items-start mb-4">
                    <h4 className="font-medium text-gray-900">
                      {trans('teacher_pages.assessments.edit.question_number', { number: qIndex + 1 })}
                    </h4>
                    <div className="flex gap-2">
                      <Button
                        type="button"
                        onClick={() => toggleQuestionExpanded(qIndex)}
                        color="secondary"
                        variant="ghost"
                        size="sm"
                      >
                        {expandedQuestions.has(qIndex)
                          ? trans('teacher_pages.assessments.edit.collapse')
                          : trans('teacher_pages.assessments.edit.expand')
                        }
                      </Button>
                      <Button
                        type="button"
                        onClick={() => handleRemoveQuestion(qIndex)}
                        color="danger"
                        variant="ghost"
                        size="sm"
                      >
                        <TrashIcon className="w-4 h-4 mr-1" />
                        {trans('common.delete')}
                      </Button>
                    </div>
                  </div>

                  {expandedQuestions.has(qIndex) && (
                    <div className="space-y-4">
                      <Textarea
                        label={trans('teacher_pages.assessments.form.question_content')}
                        value={question.content}
                        onChange={(e) => handleQuestionChange(qIndex, 'content', e.target.value)}
                        error={errors[`questions.${qIndex}.content` as keyof typeof errors]}
                        required
                        rows={2}
                      />

                      <div className="grid grid-cols-2 gap-4">
                        <Select
                          label={trans('teacher_pages.assessments.form.question_type')}
                          value={question.type}
                          onChange={(value) => handleQuestionChange(qIndex, 'type', value)}
                          options={questionTypeOptions}
                        />

                        <Input
                          type="number"
                          label={trans('teacher_pages.assessments.form.points')}
                          value={question.points}
                          onChange={(e) => handleQuestionChange(qIndex, 'points', Number(e.target.value))}
                          error={errors[`questions.${qIndex}.points` as keyof typeof errors]}
                          min={0.5}
                          step={0.5}
                          required
                        />
                      </div>

                      {(question.type === 'one_choice' || question.type === 'multiple' || question.type === 'boolean') && (
                        <div className="mt-4">
                          <div className="flex justify-between items-center mb-3">
                            <label className="block text-sm font-medium text-gray-700">
                              {trans('teacher_pages.assessments.form.choices')}
                            </label>
                            <Button
                              type="button"
                              onClick={() => handleAddChoice(qIndex)}
                              color="secondary"
                              variant="outline"
                              size="sm"
                            >
                              <PlusIcon className="w-4 h-4 mr-1" />
                              {trans('teacher_pages.assessments.edit.add_choice')}
                            </Button>
                          </div>

                          <div className="space-y-2">
                            {question.choices?.map((choice, cIndex) => (
                              <div key={cIndex} className="flex gap-2 items-start">
                                <input
                                  type={question.type === 'multiple' ? 'checkbox' : 'radio'}
                                  checked={choice.is_correct}
                                  onChange={(e) => {
                                    if (question.type === 'one_choice') {
                                      const newQuestions = [...(data.questions || [])];
                                      newQuestions[qIndex].choices = newQuestions[qIndex].choices?.map((c, i) => ({
                                        ...c,
                                        is_correct: i === cIndex
                                      })) || [];
                                      setData('questions', newQuestions);
                                    } else {
                                      handleChoiceChange(qIndex, cIndex, 'is_correct', e.target.checked);
                                    }
                                  }}
                                  className="mt-3 rounded border-gray-300 text-blue-600"
                                />
                                <div className="flex-1">
                                  <Input
                                    value={choice.content}
                                    onChange={(e) => handleChoiceChange(qIndex, cIndex, 'content', e.target.value)}
                                    error={errors[`questions.${qIndex}.choices.${cIndex}.content` as keyof typeof errors]}
                                    placeholder={trans('teacher_pages.assessments.form.choice_placeholder')}
                                    required
                                  />
                                </div>
                                <Button
                                  type="button"
                                  onClick={() => handleRemoveChoice(qIndex, cIndex)}
                                  color="danger"
                                  variant="ghost"
                                  size="sm"
                                >
                                  <TrashIcon className="w-4 h-4" />
                                </Button>
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>

          <div className="flex justify-end gap-4">
            <Button
              type="button"
              onClick={() => router.visit(route('teacher.assessments.show', assessment.id))}
              color="secondary"
              variant="outline"
            >
              {trans('common.cancel')}
            </Button>
            <Button
              type="submit"
              color="primary"
              disabled={processing}
            >
              {processing
                ? trans('common.saving')
                : trans('teacher_pages.assessments.edit.submit')
              }
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
