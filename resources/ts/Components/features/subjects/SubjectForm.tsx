import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { SubjectFormData, Level, Subject } from '@/types';
import { trans } from '@/utils';
import { Button, Input, Section, Select } from '@examena/ui';
import { route } from 'ziggy-js';

interface SubjectFormProps {
  title?: string;
  subtitle?: string;
  subject?: Subject;
  levels: Level[];
  onCancel: () => void;
}

/**
 * Reusable form component for creating and editing subjects
 */
export function SubjectForm({ title, subtitle, subject, levels, onCancel }: SubjectFormProps) {
  const isEditMode = !!subject;

  const [formData, setFormData] = useState<SubjectFormData>({
    level_id: subject?.level_id || 0,
    name: subject?.name || '',
    code: subject?.code || '',
    description: subject?.description || '',
  });

  const [errors, setErrors] = useState<Partial<Record<keyof SubjectFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: keyof SubjectFormData, value: string | number) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    const submitOptions = {
      onError: (errors: Record<string, string>) => {
        setErrors(errors as Partial<Record<keyof SubjectFormData, string>>);
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
      },
    };

    if (isEditMode) {
      router.put(route('admin.subjects.update', subject.id), formData as any, submitOptions);
    } else {
      router.post(route('admin.subjects.store'), formData as any, submitOptions);
    }
  };

  const levelOptions = [
    { value: 0, label: trans('admin_pages.subjects.select_level') },
    ...levels.map((level) => ({
      value: level.id,
      label: level.name,
    })),
  ];

  const submitButtonText = isEditMode
    ? isSubmitting
      ? trans('admin_pages.subjects.updating')
      : trans('admin_pages.subjects.update_button')
    : isSubmitting
      ? trans('admin_pages.subjects.creating')
      : trans('admin_pages.subjects.create_button');

  return (
    <form onSubmit={handleSubmit}>
      <Section title={title} subtitle={subtitle}
        actions={
          <div className="flex justify-end space-x-3">
            <Button type="button" variant="outline" color="secondary" onClick={onCancel} disabled={isSubmitting}>
              {trans('admin_pages.common.cancel')}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {submitButtonText}
            </Button>
          </div>
        }
      >
        <div className="space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Input
                label={trans('admin_pages.subjects.name')}
                name="name"
                value={formData.name}
                onChange={(e) => handleChange('name', e.target.value)}
                error={errors.name}
                required
                placeholder={trans('admin_pages.subjects.name_placeholder')}
              />

              <Input
                label={trans('admin_pages.subjects.code')}
                name="code"
                value={formData.code}
                onChange={(e) => handleChange('code', e.target.value)}
                error={errors.code}
                required
                placeholder={trans('admin_pages.subjects.code_placeholder')}
              />
            </div>

            <Select
              label={trans('admin_pages.subjects.level')}
              name="level_id"
              value={formData.level_id}
              onChange={(value) => handleChange('level_id', Number(value))}
              error={errors.level_id}
              required
              options={levelOptions}
            />

            <Input
              label={trans('admin_pages.subjects.description')}
              name="description"
              value={formData.description || ''}
              onChange={(e) => handleChange('description', e.target.value)}
              error={errors.description}
              placeholder={trans('admin_pages.subjects.description_placeholder')}
              helperText={trans('admin_pages.subjects.description_helper')}
            />
          </div>
        </div>
      </Section>
    </form>
  );
}

export default SubjectForm;
