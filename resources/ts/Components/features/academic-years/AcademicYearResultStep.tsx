import { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Button, Section } from '@evalium/ui';
import { CheckCircleIcon } from '@heroicons/react/24/solid';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAcademicYearWizard } from '@/contexts/AcademicYearWizardContext';

/**
 * Result screen after the academic year creation wizard completes.
 * Displays the created year details and links to relevant admin pages.
 */
export function AcademicYearResultStep() {
  const { t } = useTranslations();
  const { state } = useAcademicYearWizard();
  const { result } = state;

  const classesMessage = useMemo(() => {
    if (!result || result.duplicated_classes_count === 0) {
      return t('admin_pages.academic_years.wizard_result_no_classes');
    }
    return t('admin_pages.academic_years.wizard_result_classes_duplicated', {
      count: result.duplicated_classes_count,
    });
  }, [result, t]);

  const actionsSlot = (
    <>
      <Link href={route('admin.classes.index')}>
        <Button type="button" variant="outline" color="secondary">
          {t('admin_pages.academic_years.wizard_result_manage')}
        </Button>
      </Link>
      <Link href={route('admin.academic-years.archives')}>
        <Button type="button" variant="solid" color="primary">
          {t('admin_pages.academic_years.wizard_result_view_years')}
        </Button>
      </Link>
    </>
  );

  return (
    <Section
      title={t('admin_pages.academic_years.wizard_result_title')}
      subtitle={t('admin_pages.academic_years.wizard_result_subtitle')}
      actions={actionsSlot}
    >
      <div className="flex flex-col items-center justify-center py-8 gap-4">
        <CheckCircleIcon className="h-16 w-16 text-green-500" />
        <div className="text-center">
          <p className="text-xl font-semibold text-gray-900">{result?.year.name}</p>
          <p className="text-sm text-gray-500 mt-2">{classesMessage}</p>
        </div>
      </div>
    </Section>
  );
}
