import React from 'react';
import { trans } from '@/utils';
import { Checkbox, Input, Select } from '@examena/ui';
import { MarkdownEditor } from '@examena/ui';
import { ClassSubject, AssessmentType } from '@/types';

interface AssessmentGeneralConfigProps {
  data: {
    title: string;
    description: string;
    duration: number;
    scheduled_date?: string;
    type: AssessmentType;
    class_subject_id: number;
    is_published: boolean;
  };
  errors: {
    title?: string;
    description?: string;
    duration?: string;
    scheduled_date?: string;
    type?: string;
    class_subject_id?: string;
    is_published?: string;
  };
  onFieldChange: (field: string, value: string | number | boolean) => void;
  classSubjects: ClassSubject[];
}

const AssessmentGeneralConfig: React.FC<AssessmentGeneralConfigProps> = ({
  data,
  errors,
  onFieldChange,
  classSubjects
}) => {
  const assessmentTypeOptions = [
    { value: 'devoir', label: trans('components.assessment_general_config.type_devoir') },
    { value: 'examen', label: trans('components.assessment_general_config.type_examen') },
    { value: 'tp', label: trans('components.assessment_general_config.type_tp') },
    { value: 'controle', label: trans('components.assessment_general_config.type_controle') },
    { value: 'projet', label: trans('components.assessment_general_config.type_projet') }
  ];

  const classSubjectOptions = classSubjects.map(cs => ({
    value: cs.id.toString(),
    label: `${cs.class?.name} - ${cs.subject?.name}`
  }));

  return (
    <div className="space-y-6">
      <h3 className="text-lg font-medium text-gray-900">
        {trans('components.assessment_general_config.title')}
      </h3>

      <Checkbox
        label={trans('components.assessment_general_config.published_label')}
        checked={data.is_published}
        onChange={(e) => onFieldChange('is_published', e.target.checked)}
      />

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div className="md:col-span-2 lg:col-span-1">
          <Input
            label={trans('components.assessment_general_config.assessment_title_label')}
            type="text"
            value={data.title}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('title', e.target.value)}
            error={errors.title}
            required
          />
        </div>

        <div>
          <Select
            label={trans('components.assessment_general_config.type_label')}
            value={data.type}
            onChange={(value) => onFieldChange('type', value)}
            error={errors.type}
            options={assessmentTypeOptions}
            required
          />
        </div>

        <div>
          <Input
            label={trans('components.assessment_general_config.duration_label')}
            type="number"
            value={data.duration?.toString() || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('duration', parseInt(e.target.value))}
            error={errors.duration}
            min="1"
            required
          />
        </div>

        <div>
          <Select
            label={trans('components.assessment_general_config.class_subject_label')}
            value={String(data.class_subject_id)}
            onChange={(value) => {
              const parsed = typeof value === 'number' ? value : parseInt(value, 10);
              if (!isNaN(parsed)) {
                onFieldChange('class_subject_id', parsed);
              }
            }}
            error={errors.class_subject_id}
            options={classSubjectOptions}
            required
            placeholder={trans('components.assessment_general_config.class_subject_placeholder')}
          />
        </div>

        <div className="md:col-span-2 lg:col-span-1">
          <Input
            label={trans('components.assessment_general_config.scheduled_date_label')}
            type="datetime-local"
            value={data.scheduled_date || ''}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('scheduled_date', e.target.value)}
            error={errors.scheduled_date}
          />
        </div>
      </div>

      <div>
        <MarkdownEditor
          value={data.description}
          onChange={(value) => onFieldChange('description', value)}
          placeholder={trans('components.assessment_general_config.description_placeholder')}
          label={trans('components.assessment_general_config.description_label')}
          rows={4}
          error={errors.description}
          helpText={trans('components.assessment_general_config.description_help')}
        />
      </div>
    </div>
  );
};

export { AssessmentGeneralConfig };
