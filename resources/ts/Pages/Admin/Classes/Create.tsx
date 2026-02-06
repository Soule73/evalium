import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassFormData, AcademicYear, Level } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Button, Section, Input, Select } from '@/Components';
import { route } from 'ziggy-js';

interface Props {
  academicYears: AcademicYear[];
  levels: Level[];
}

export default function ClassCreate({ academicYears, levels }: Props) {
  const [formData, setFormData] = useState<ClassFormData>({
    academic_year_id: 0,
    level_id: 0,
    name: '',
    max_students: 30,
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

    router.post(route('admin.classes.store'), formData as any, {
      onError: (errors) => {
        setErrors(errors as any);
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
      },
    });
  };

  const handleCancel = () => {
    router.visit(route('admin.classes.index'));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.classes.create_title')}
      breadcrumb={breadcrumbs.admin.createClass()}
    >
      <Section
        title={trans('admin_pages.classes.create_title')}
        subtitle={trans('admin_pages.classes.create_subtitle')}
      >
        <form onSubmit={handleSubmit} className="space-y-6">
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
                onChange={(value) => handleChange('academic_year_id', value)}
                error={errors.academic_year_id}
                required
                options={[
                  { value: 0, label: trans('admin_pages.classes.select_academic_year') },
                  ...academicYears.map((year) => ({
                    value: year.id,
                    label: year.name
                  }))
                ]}
              />

              <Select
                label={trans('admin_pages.classes.level')}
                name="level_id"
                value={formData.level_id}
                onChange={(value) => handleChange('level_id', value)}
                error={errors.level_id}
                required
                options={[
                  { value: 0, label: trans('admin_pages.classes.select_level') },
                  ...levels.map((level) => ({
                    value: level.id,
                    label: level.name
                  }))
                ]}
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

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {trans('admin_pages.common.cancel')}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {isSubmitting ? trans('admin_pages.classes.creating') : trans('admin_pages.classes.create_button')}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
