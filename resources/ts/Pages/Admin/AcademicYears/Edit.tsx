import { type FormEvent, useCallback, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type AcademicYear, type AcademicYearFormData, type PageProps } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { AcademicYearForm, toDateInputValue } from '@/Components/features/academic-years';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  academicYear: AcademicYear;
}

export default function AcademicYearEdit({ academicYear }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const [formData, setFormData] = useState<AcademicYearFormData>({
    name: academicYear.name,
    start_date: toDateInputValue(academicYear.start_date),
    end_date: toDateInputValue(academicYear.end_date),
    is_current: academicYear.is_current,
    semesters: (academicYear.semesters || []).map((s) => ({
      id: s.id,
      name: s.name,
      start_date: toDateInputValue(s.start_date),
      end_date: toDateInputValue(s.end_date),
    })),
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = useCallback((e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.put(
      route('admin.academic-years.update', academicYear.id),
      formData as unknown as Record<string, FormDataConvertible>,
      {
        onError: (errs) => {
          setErrors(errs as Record<string, string>);
          setIsSubmitting(false);
        },
        onSuccess: () => setIsSubmitting(false),
      }
    );
  }, [formData, academicYear.id]);

  const handleCancel = useCallback(() => {
    router.visit(route('admin.academic-years.archives'));
  }, []);

  const handleErrorsClear = useCallback((field: string) => {
    setErrors((prev) => ({ ...prev, [field]: '' }));
  }, []);

  const warningMessage = useMemo(() => {
    if (academicYear.is_current && !formData.is_current) {
      return t('admin_pages.academic_years.edit_warning');
    }
    return undefined;
  }, [academicYear.is_current, formData.is_current, t]);

  const translations = useMemo(() => ({
    pageTitle: t('admin_pages.academic_years.edit_page_title'),
    sectionTitle: t('admin_pages.academic_years.edit_title', { name: academicYear.name }),
    sectionSubtitle: t('admin_pages.academic_years.edit_subtitle'),
    updating: t('admin_pages.common.updating'),
    update: t('admin_pages.common.update'),
  }), [t, academicYear.name]);

  return (
    <AuthenticatedLayout
      title={translations.pageTitle}
      breadcrumb={breadcrumbs.admin.editAcademicYear(academicYear)}
    >
      <AcademicYearForm
        formData={formData}
        errors={errors}
        isSubmitting={isSubmitting}
        sectionTitle={translations.sectionTitle}
        sectionSubtitle={translations.sectionSubtitle}
        submitLabel={translations.update}
        submittingLabel={translations.updating}
        onFormDataChange={setFormData}
        onErrorsClear={handleErrorsClear}
        onSubmit={handleSubmit}
        onCancel={handleCancel}
        warningMessage={warningMessage}
      />
    </AuthenticatedLayout>
  );
}
