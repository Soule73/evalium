import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type AcademicYear, type Semester, type ClassModel, type PageProps } from '@/types';
import { formatDate, hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, Badge, SemesterCard } from '@/Components';
import { route } from 'ziggy-js';
import { CalendarIcon, AcademicCapIcon, CheckCircleIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  academicYear: AcademicYear & {
    semesters: Semester[];
    classes: ClassModel[];
  };
}

export default function AcademicYearShow({ academicYear, auth }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const canUpdate = hasPermission(auth.permissions, 'update academic years');
  const canDelete = hasPermission(auth.permissions, 'delete academic years');

  const handleEdit = () => {
    router.visit(route('admin.academic-years.edit', academicYear.id));
  };

  const handleDelete = () => {
    if (confirm(t('admin_pages.academic_years.confirm_delete'))) {
      router.delete(route('admin.academic-years.destroy', academicYear.id));
    }
  };

  const handleBack = () => {
    router.visit(route('admin.academic-years.index'));
  };

  const translations = useMemo(() => ({
    showSubtitle: t('admin_pages.academic_years.show_subtitle'),
    back: t('admin_pages.common.back'),
    edit: t('admin_pages.common.edit'),
    delete: t('admin_pages.common.delete'),
    period: t('admin_pages.academic_years.period'),
    status: t('admin_pages.academic_years.status'),
    current: t('admin_pages.academic_years.current'),
    archived: t('admin_pages.academic_years.archived'),
    semestersTitle: t('admin_pages.academic_years.semesters_title'),
    semestersSubtitle: t('admin_pages.academic_years.semesters_subtitle'),
    noSemesters: t('admin_pages.academic_years.no_semesters'),
    classesTitle: t('admin_pages.academic_years.classes_title'),
    classesSubtitle: t('admin_pages.academic_years.classes_subtitle'),
    students: t('admin_pages.academic_years.students'),
    subjects: t('admin_pages.academic_years.subjects'),
    noClasses: t('admin_pages.academic_years.no_classes'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={academicYear.name}
      breadcrumb={breadcrumbs.admin.showAcademicYear(academicYear)}
    >
      <div className="space-y-6">
        <Section
          title={academicYear.name}
          subtitle={translations.showSubtitle}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {translations.back}
              </Button>
              {canUpdate && (
                <Button size="sm" variant="solid" color="primary" onClick={handleEdit}>
                  {translations.edit}
                </Button>
              )}
              {canDelete && !academicYear.is_current && (
                <Button size="sm" variant="outline" color="danger" onClick={handleDelete}>
                  {translations.delete}
                </Button>
              )}
            </div>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="flex items-start space-x-3">
              <CalendarIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {translations.period}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {formatDate(academicYear.start_date)} - {formatDate(academicYear.end_date)}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <CheckCircleIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {translations.status}
                </div>
                <div className="mt-1">
                  <Badge
                    label={
                      academicYear.is_current
                        ? translations.current
                        : translations.archived
                    }
                    type={academicYear.is_current ? 'success' : 'info'}
                  />
                </div>
              </div>
            </div>
          </div>
        </Section>

        <Section
          title={translations.semestersTitle}
          subtitle={translations.semestersSubtitle}
        >
          {academicYear.semesters && academicYear.semesters.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {academicYear.semesters.map((semester) => (
                <SemesterCard
                  key={semester.id}
                  semester={semester}
                  showClassSubjects={true}
                />
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              {translations.noSemesters}
            </div>
          )}
        </Section>

        <Section
          title={translations.classesTitle}
          subtitle={translations.classesSubtitle}
        >
          {academicYear.classes && academicYear.classes.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {academicYear.classes.map((classItem) => (
                <div
                  key={classItem.id}
                  className="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors cursor-pointer"
                  onClick={() => router.visit(route('admin.classes.show', classItem.id))}
                >
                  <div className="flex items-center space-x-2 mb-2">
                    <AcademicCapIcon className="w-5 h-5 text-gray-400" />
                    <h3 className="text-base font-medium text-gray-900">
                      {classItem.level?.name} - {classItem.name}
                    </h3>
                  </div>
                  <div className="text-sm text-gray-600">
                    {classItem.active_enrollments_count || 0} / {classItem.max_students}{' '}
                    {translations.students}
                  </div>
                  {classItem.subjects_count !== undefined && (
                    <div className="mt-1 text-xs text-gray-500">
                      {classItem.subjects_count} {translations.subjects}
                    </div>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              {translations.noClasses}
            </div>
          )}
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
