import { type FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type AcademicYearFormData } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { Input, Checkbox } from '@/Components';
import { route } from 'ziggy-js';

export default function AcademicYearCreate() {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

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

    router.post(route('admin.academic-years.store'), formData as unknown as unknown as Record<string, FormDataConvertible>, {
      onError: (errors) => {
        setErrors(errors as Partial<Record<keyof AcademicYearFormData, string>>);
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
      },
    });
  };

  const handleCancel = () => {
    router.visit(route('admin.academic-years.archives'));
  };

  const translations = useMemo(() => ({
    createPageTitle: t('admin_pages.academic_years.create_page_title'),
    createTitle: t('admin_pages.academic_years.create_title'),
    createSubtitle: t('admin_pages.academic_years.create_subtitle'),
    nameLabel: t('admin_pages.academic_years.name_label'),
    namePlaceholder: t('admin_pages.academic_years.name_placeholder'),
    nameHelper: t('admin_pages.academic_years.name_helper'),
    startDateLabel: t('admin_pages.academic_years.start_date_label'),
    endDateLabel: t('admin_pages.academic_years.end_date_label'),
    isCurrent: t('admin_pages.academic_years.is_current'),
    isCurrentHelper: t('admin_pages.academic_years.is_current_helper'),
    createNote: t('admin_pages.academic_years.create_note'),
    cancel: t('admin_pages.common.cancel'),
    creating: t('admin_pages.common.creating'),
    create: t('admin_pages.common.create'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={translations.createPageTitle}
      breadcrumb={breadcrumbs.admin.createAcademicYear()}
    >
      <Section
        title={translations.createTitle}
        subtitle={translations.createSubtitle}
      >
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <Input
              label={translations.nameLabel}
              name="name"
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              error={errors.name}
              required
              placeholder={translations.namePlaceholder}
              helperText={translations.nameHelper}
            />

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Input
                label={translations.startDateLabel}
                name="start_date"
                type="date"
                value={formData.start_date}
                onChange={(e) => handleChange('start_date', e.target.value)}
                error={errors.start_date}
                required
              />

              <Input
                label={translations.endDateLabel}
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
                label={translations.isCurrent}
                name="is_current"
                checked={formData.is_current || false}
                onChange={(e) => handleChange('is_current', e.target.checked)}
              />
              <p className="text-sm text-gray-500 mt-1">{translations.isCurrentHelper}</p>
            </div>
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-sm text-blue-800">{translations.createNote}</p>
          </div>

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {translations.cancel}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {isSubmitting ? translations.creating : translations.create}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
