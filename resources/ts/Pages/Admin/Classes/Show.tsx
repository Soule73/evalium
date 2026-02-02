import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassModel, Enrollment, ClassSubject, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section, Badge } from '@/Components';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserGroupIcon, BookOpenIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  class: ClassModel & {
    enrollments?: Enrollment[];
    class_subjects?: ClassSubject[];
  };
}

export default function ClassShow({ class: classItem, auth }: Props) {
  const canUpdate = hasPermission(auth.permissions, 'update classes');
  const canDelete = hasPermission(auth.permissions, 'delete classes');

  const handleEdit = () => {
    router.visit(route('admin.classes.edit', classItem.id));
  };

  const handleDelete = () => {
    if (confirm(trans('admin_pages.classes.delete_confirm'))) {
      router.delete(route('admin.classes.destroy', classItem.id));
    }
  };

  const handleBack = () => {
    router.visit(route('admin.classes.index'));
  };

  const handleManageEnrollments = () => {
    router.visit(route('admin.enrollments.index', { class_id: classItem.id }));
  };

  const handleManageSubjects = () => {
    router.visit(route('admin.class-subjects.index', { class_id: classItem.id }));
  };

  const activeEnrollments = classItem.enrollments?.filter(e => e.status === 'active') || [];
  const enrollmentPercentage = classItem.max_students > 0
    ? (activeEnrollments.length / classItem.max_students) * 100
    : 0;

  return (
    <AuthenticatedLayout
      title={classItem.display_name || classItem.name}
      breadcrumb={breadcrumbs.admin.showClass(classItem)}
    >
      <div className="space-y-6">
        <Section
          title={classItem.display_name || classItem.name}
          subtitle={trans('admin_pages.classes.show_subtitle')}
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
              {canDelete && (
                <Button size="sm" variant="outline" color="danger" onClick={handleDelete}>
                  {trans('admin_pages.common.delete')}
                </Button>
              )}
            </div>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.level')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {classItem.level?.name || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <CalendarIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.academic_year')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {classItem.academic_year?.name || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <UserGroupIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.students')}
                </div>
                <div className="mt-1 flex items-center space-x-2">
                  <span className="text-sm font-semibold text-gray-900">
                    {activeEnrollments.length} / {classItem.max_students}
                  </span>
                  {enrollmentPercentage >= 90 && (
                    <Badge label={trans('admin_pages.classes.full')} type="warning" size="sm" />
                  )}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <BookOpenIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.subjects')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {classItem.class_subjects?.length || 0}
                </div>
              </div>
            </div>
          </div>
        </Section>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Section
            title={trans('admin_pages.classes.enrollments_section')}
            subtitle={trans('admin_pages.classes.enrollments_section_subtitle')}
            actions={
              <Button size="sm" variant="outline" color="primary" onClick={handleManageEnrollments}>
                {trans('admin_pages.classes.manage_enrollments')}
              </Button>
            }
          >
            {activeEnrollments.length > 0 ? (
              <div className="space-y-2">
                {activeEnrollments.slice(0, 5).map((enrollment) => (
                  <div
                    key={enrollment.id}
                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div>
                      <div className="text-sm font-medium text-gray-900">
                        {enrollment.student?.name}
                      </div>
                      <div className="text-xs text-gray-500">
                        {enrollment.student?.email}
                      </div>
                    </div>
                    <Badge
                      label={trans(`admin_pages.classes.status_${enrollment.status}`)}
                      type="success"
                      size="sm"
                    />
                  </div>
                ))}
                {activeEnrollments.length > 5 && (
                  <div className="text-sm text-gray-500 text-center pt-2">
                    {trans('admin_pages.classes.and_more', { count: activeEnrollments.length - 5 })}
                  </div>
                )}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                {trans('admin_pages.classes.no_enrollments')}
              </div>
            )}
          </Section>

          <Section
            title={trans('admin_pages.classes.subjects_section')}
            subtitle={trans('admin_pages.classes.subjects_section_subtitle')}
            actions={
              <Button size="sm" variant="outline" color="primary" onClick={handleManageSubjects}>
                {trans('admin_pages.classes.manage_subjects')}
              </Button>
            }
          >
            {classItem.class_subjects && classItem.class_subjects.length > 0 ? (
              <div className="space-y-2">
                {classItem.class_subjects.map((classSubject) => (
                  <div
                    key={classSubject.id}
                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div>
                      <div className="text-sm font-medium text-gray-900">
                        {classSubject.subject?.name}
                      </div>
                      {classSubject.teacher && (
                        <div className="text-xs text-gray-500">
                          {classSubject.teacher.name}
                        </div>
                      )}
                    </div>
                    <Badge
                      label={`Coef. ${classSubject.coefficient}`}
                      type="info"
                      size="sm"
                    />
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                {trans('admin_pages.classes.no_subjects')}
              </div>
            )}
          </Section>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
