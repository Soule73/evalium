import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { AcademicYear, Semester, ClassModel, PageProps } from '@/types';
import { breadcrumbs, trans, formatDate, hasPermission } from '@/utils';
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
  const canUpdate = hasPermission(auth.permissions, 'update academic years');
  const canDelete = hasPermission(auth.permissions, 'delete academic years');

  const handleEdit = () => {
    router.visit(route('admin.academic-years.edit', academicYear.id));
  };

  const handleDelete = () => {
    if (confirm(trans('admin_pages.academic_years.confirm_delete'))) {
      router.delete(route('admin.academic-years.destroy', academicYear.id));
    }
  };

  const handleBack = () => {
    router.visit(route('admin.academic-years.index'));
  };

  return (
    <AuthenticatedLayout
      title={academicYear.name}
      breadcrumb={breadcrumbs.admin.showAcademicYear(academicYear)}
    >
      <div className="space-y-6">
        <Section
          title={academicYear.name}
          subtitle={trans('admin_pages.academic_years.show_subtitle')}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {trans('admin_pages.common.back')}
              </Button>
              {canUpdate && (
                <Button size="sm" variant="solid" color="primary" onClick={handleEdit}>
                  {trans('admin_pages.common.edit')}
                </Button>
              )}
              {canDelete && !academicYear.is_current && (
                <Button size="sm" variant="outline" color="danger" onClick={handleDelete}>
                  {trans('admin_pages.common.delete')}
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
                  {trans('admin_pages.academic_years.period')}
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
                  {trans('admin_pages.academic_years.status')}
                </div>
                <div className="mt-1">
                  <Badge
                    label={
                      academicYear.is_current
                        ? trans('admin_pages.academic_years.current')
                        : trans('admin_pages.academic_years.archived')
                    }
                    type={academicYear.is_current ? 'success' : 'info'}
                  />
                </div>
              </div>
            </div>
          </div>
        </Section>

        <Section
          title={trans('admin_pages.academic_years.semesters_title')}
          subtitle={trans('admin_pages.academic_years.semesters_subtitle')}
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
              {trans('admin_pages.academic_years.no_semesters')}
            </div>
          )}
        </Section>

        <Section
          title={trans('admin_pages.academic_years.classes_title')}
          subtitle={trans('admin_pages.academic_years.classes_subtitle')}
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
                    {trans('admin_pages.academic_years.students')}
                  </div>
                  {classItem.subjects_count !== undefined && (
                    <div className="mt-1 text-xs text-gray-500">
                      {classItem.subjects_count} {trans('admin_pages.academic_years.subjects')}
                    </div>
                  )}
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              {trans('admin_pages.academic_years.no_classes')}
            </div>
          )}
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
