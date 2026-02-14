import { type FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type AcademicYear, type AcademicYearFormData, type PageProps } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Checkbox, Input, Section } from '@/Components';
// import { Input, Checkbox } from '@/Components/forms';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  academicYear: AcademicYear;
}

export default function AcademicYearEdit({ academicYear }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const [formData, setFormData] = useState<AcademicYearFormData>({
    name: academicYear.name,
    start_date: academicYear.start_date,
    end_date: academicYear.end_date,
    is_current: academicYear.is_current,
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

    router.put(route('admin.academic-years.update', academicYear.id), formData as unknown as unknown as Record<string, FormDataConvertible>, {
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
    router.visit(route('admin.academic-years.show', academicYear.id));
  };

  const translations = useMemo(() => ({
    editPageTitle: t('admin_pages.academic_years.edit_page_title'),
    editSubtitle: t('admin_pages.academic_years.edit_subtitle'),
    nameLabel: t('admin_pages.academic_years.name_label'),
    namePlaceholder: t('admin_pages.academic_years.name_placeholder'),
    startDateLabel: t('admin_pages.academic_years.start_date_label'),
    endDateLabel: t('admin_pages.academic_years.end_date_label'),
    isCurrent: t('admin_pages.academic_years.is_current'),
    isCurrentHelper: t('admin_pages.academic_years.is_current_helper'),
    editWarning: t('admin_pages.academic_years.edit_warning'),
    cancel: t('admin_pages.common.cancel'),
    updating: t('admin_pages.common.updating'),
    update: t('admin_pages.common.update'),
  }), [t]);

  const editTitle = useMemo(() => t('admin_pages.academic_years.edit_title', { name: academicYear.name }), [t, academicYear.name]);

  return (
    <AuthenticatedLayout
      title={translations.editPageTitle}
      breadcrumb={breadcrumbs.admin.editAcademicYear(academicYear)}
    >
      <Section
        title={editTitle}
        subtitle={translations.editSubtitle}
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

          {academicYear.is_current && !formData.is_current && (
            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <p className="text-sm text-yellow-800">
                {translations.editWarning}
              </p>
            </div>
          )}

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {translations.cancel}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {isSubmitting ? translations.updating : translations.update}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
