import { FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { ClassFormData, ClassModel, AcademicYear, Level } from '@/types';
import { trans } from '@/utils';
import { Button, Input, Section, Select } from '@examena/ui';
import { route } from 'ziggy-js';

interface ClassFormProps {
  title?: string;
  subtitle?: string;
  classItem?: ClassModel;
  academicYears: AcademicYear[];
  levels: Level[];
  onCancel: () => void;
}

/**
 * Reusable form component for creating and editing classes
 */
export function ClassForm({
  title,
  subtitle,
  classItem,
  academicYears,
  levels,
  onCancel,
}: ClassFormProps) {
  const isEditMode = !!classItem;

  const [formData, setFormData] = useState<ClassFormData>({
    academic_year_id: classItem?.academic_year_id || 0,
    level_id: classItem?.level_id || 0,
    name: classItem?.name || '',
    max_students: classItem?.max_students || 30,
  });

  const [errors, setErrors] = useState<Partial<Record<keyof ClassFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: keyof ClassFormData, value: string | number) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    const submitOptions = {
      onError: (errors: Record<string, string>) => {
        setErrors(errors as Partial<Record<keyof ClassFormData, string>>);
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
      },
    };

    if (isEditMode) {
      router.put(route('admin.classes.update', classItem.id), formData as any, submitOptions);
    } else {
      router.post(route('admin.classes.store'), formData as any, submitOptions);
    }
  };

  const academicYearOptions = useMemo(() => [
    { value: 0, label: trans('admin_pages.classes.select_academic_year') },
    ...academicYears.map((year) => ({
      value: year.id,
      label: year.name,
    })),
  ], [academicYears]);

  const levelOptions = useMemo(() => [
    { value: 0, label: trans('admin_pages.classes.select_level') },
    ...levels.map((level) => ({
      value: level.id,
      label: level.name,
    })),
  ], [levels]);

  const submitButtonText = isEditMode
    ? isSubmitting
      ? trans('admin_pages.classes.updating')
      : trans('admin_pages.classes.update_button')
    : isSubmitting
      ? trans('admin_pages.classes.creating')
      : trans('admin_pages.classes.create_button');

  return (
    <Section
      title={title}
      subtitle={subtitle}
      actions={
        <div className="flex justify-end space-x-3">
          <Button type="button" variant="outline" color="secondary" onClick={onCancel} disabled={isSubmitting}>
            {trans('admin_pages.common.cancel')}
          </Button>
          <Button type="submit" variant="solid" color="primary" disabled={isSubmitting} form="class-form">
            {submitButtonText}
          </Button>
        </div>
      }
    >
      <form id="class-form" onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 gap-6">
          <Input
            label={trans('admin_pages.classes.name')}
            name="name"
            value={formData.name}
            onChange={(e) => handleChange('name', e.target.value)}
            error={errors.name}
            required
            placeholder={trans('admin_pages.classes.name_placeholder')}
            helperText={trans('admin_pages.classes.name_helper')}
          />

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Select
              label={trans('admin_pages.classes.academic_year')}
              name="academic_year_id"
              value={formData.academic_year_id}
              onChange={(value) => handleChange('academic_year_id', Number(value))}
              error={errors.academic_year_id}
              required
              options={academicYearOptions}
            />

            <Select
              label={trans('admin_pages.classes.level')}
              name="level_id"
              value={formData.level_id}
              onChange={(value) => handleChange('level_id', Number(value))}
              error={errors.level_id}
              required
              options={levelOptions}
            />
          </div>

          <Input
            label={trans('admin_pages.classes.max_students')}
            name="max_students"
            type="number"
            value={formData.max_students?.toString() || ''}
            onChange={(e) => handleChange('max_students', parseInt(e.target.value) || 0)}
            error={errors.max_students}
            required
            min={1}
            helperText={trans('admin_pages.classes.max_students_helper')}
          />
        </div>
      </form>
    </Section>
  );
}
