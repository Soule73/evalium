import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { AcademicYearFormData } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Button, Section } from '@/Components';
import { Input, Checkbox } from '@/Components';
import { route } from 'ziggy-js';

export default function AcademicYearCreate() {
  const [formData, setFormData] = useState<AcademicYearFormData>({
    name: '',
    start_date: '',
    end_date: '',
    is_current: false,
  });

  const [errors, setErrors] = useState<Partial<Record<keyof AcademicYearFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: keyof AcademicYearFormData, value: string | boolean) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.post(route('admin.academic-years.store'), formData as any, {
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
    router.visit(route('admin.academic-years.index'));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.academic_years.create_page_title')}
      breadcrumb={breadcrumbs.admin.createAcademicYear()}
    >
      <Section
        title={trans('admin_pages.academic_years.create_title')}
        subtitle={trans('admin_pages.academic_years.create_subtitle')}
      >
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <Input
              label={trans('admin_pages.academic_years.name_label')}
              name="name"
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              error={errors.name}
              required
              placeholder={trans('admin_pages.academic_years.name_placeholder')}
              helperText={trans('admin_pages.academic_years.name_helper')}
            />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Input
                label={trans('admin_pages.academic_years.start_date_label')}
                name="start_date"
                type="date"
                value={formData.start_date}
                onChange={(e) => handleChange('start_date', e.target.value)}
                error={errors.start_date}
                required
              />

              <Input
                label={trans('admin_pages.academic_years.end_date_label')}
                name="end_date"
                type="date"
                value={formData.end_date}
                onChange={(e) => handleChange('end_date', e.target.value)}
                error={errors.end_date}
                required
              />
            </div>

            <div>
              <Checkbox
                label={trans('admin_pages.academic_years.is_current')}
                name="is_current"
                checked={formData.is_current || false}
                onChange={(e) => handleChange('is_current', e.target.checked)}
              />
              <p className="text-sm text-gray-500 mt-1">{trans('admin_pages.academic_years.is_current_helper')}</p>
            </div>
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-sm text-blue-800">{trans('admin_pages.academic_years.create_note')}</p>
          </div>

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {trans('admin_pages.common.cancel')}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {isSubmitting ? trans('admin_pages.common.creating') : trans('admin_pages.common.create')}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
