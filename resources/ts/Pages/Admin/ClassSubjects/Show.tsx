import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassSubject, ClassSubjectHistory, PageProps } from '@/types';
import { breadcrumbs, trans, formatDate, hasPermission } from '@/utils';
import { Button, Section, Badge } from '@/Components';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserIcon, HashtagIcon, CalendarIcon, ClockIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  classSubject: ClassSubject;
  history?: ClassSubjectHistory[];
}

export default function ClassSubjectShow({ classSubject, history, auth }: Props) {
  const canUpdate = hasPermission(auth.permissions, 'update class subjects');

  const handleReplaceTeacher = () => {
    router.visit(route('admin.class-subjects.replace-teacher', classSubject.id));
  };

  const handleUpdateCoefficient = () => {
    router.visit(route('admin.class-subjects.edit-coefficient', classSubject.id));
  };

  const handleArchive = () => {
    if (confirm(trans('admin_pages.class_subjects.archive_confirm_message'))) {
      router.post(route('admin.class-subjects.archive', classSubject.id));
    }
  };

  const handleBack = () => {
    router.visit(route('admin.class-subjects.index'));
  };

  const isActive = !classSubject.valid_to;

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.class_subjects.show_title')}
      breadcrumb={breadcrumbs.admin.showClassSubject(classSubject)}
    >
      <div className="space-y-6">
        <Section
          title={trans('admin_pages.class_subjects.show_title')}
          subtitle={trans('admin_pages.class_subjects.show_subtitle')}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {trans('admin_pages.common.back')}
              </Button>
              {canUpdate && isActive && (
                <>
                  <Button size="sm" variant="solid" color="primary" onClick={handleReplaceTeacher}>
                    {trans('admin_pages.class_subjects.replace_teacher')}
                  </Button>
                  <Button size="sm" variant="outline" color="warning" onClick={handleUpdateCoefficient}>
                    {trans('admin_pages.class_subjects.update_coefficient')}
                  </Button>
                  <Button size="sm" variant="outline" color="danger" onClick={handleArchive}>
                    {trans('admin_pages.class_subjects.archive')}
                  </Button>
                </>
              )}
            </div>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.class_subjects.class')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {classSubject.class?.display_name || classSubject.class?.name}
                </div>
                <div className="text-xs text-gray-500">
                  {classSubject.class?.level?.name}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.class_subjects.subject')}
                </div>
                <div className="mt-1 flex items-center space-x-2">
                  <Badge label={classSubject.subject?.code || ''} type="info" size="sm" />
                  <span className="text-sm font-semibold text-gray-900">
                    {classSubject.subject?.name}
                  </span>
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <UserIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.class_subjects.teacher')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {classSubject.teacher?.name}
                </div>
                <div className="text-xs text-gray-500">
                  {classSubject.teacher?.email}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <HashtagIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.class_subjects.coefficient')}
                </div>
                <div className="mt-1">
                  <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <ClockIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.common.status')}
                </div>
                <div className="mt-1">
                  <Badge
                    label={isActive ? trans('admin_pages.class_subjects.active') : trans('admin_pages.class_subjects.archived')}
                    type={isActive ? 'success' : 'gray'}
                    size="sm"
                  />
                </div>
              </div>
            </div>
          </div>

          <div className="mt-6 pt-6 border-t border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <div className="text-sm font-medium text-gray-500 mb-2">
                {trans('admin_pages.class_subjects.validity_period')}
              </div>
              <div className="flex items-center space-x-2 text-sm text-gray-700">
                <CalendarIcon className="w-4 h-4 text-gray-400" />
                <span>
                  {formatDate(classSubject.valid_from)}
                  {classSubject.valid_to && ` - ${formatDate(classSubject.valid_to)}`}
                </span>
              </div>
            </div>

            {classSubject.semester && (
              <div>
                <div className="text-sm font-medium text-gray-500 mb-2">
                  {trans('admin_pages.class_subjects.semester')}
                </div>
                <Badge label={`S${classSubject.semester.order_number}`} type="info" size="sm" />
              </div>
            )}
          </div>
        </Section>

        {history && history.length > 0 && (
          <Section
            title={trans('admin_pages.class_subjects.history_title')}
            subtitle={trans('admin_pages.class_subjects.history_subtitle')}
          >
            <div className="space-y-4">
              {history.map((item, index) => (
                <div key={item.id} className="border border-gray-200 rounded-lg p-4 bg-gray-50">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center space-x-3">
                        <UserIcon className="w-5 h-5 text-gray-400" />
                        <div>
                          <div className="text-sm font-medium text-gray-900">
                            {item.teacher?.name}
                          </div>
                          <div className="text-xs text-gray-500">
                            {item.teacher?.email}
                          </div>
                        </div>
                      </div>

                      <div className="mt-3 flex items-center space-x-4 text-sm text-gray-600">
                        <div className="flex items-center space-x-2">
                          <CalendarIcon className="w-4 h-4 text-gray-400" />
                          <span>
                            {formatDate(item.valid_from)}
                            {item.valid_to && ` - ${formatDate(item.valid_to)}`}
                          </span>
                        </div>
                      </div>

                      {item.replaced_by_user && (
                        <div className="mt-2 text-xs text-gray-500">
                          {trans('admin_pages.class_subjects.replaced_by')}: {item.replaced_by_user.name}
                        </div>
                      )}
                    </div>

                    <Badge
                      label={index === 0 && !item.valid_to ? trans('admin_pages.class_subjects.current') : trans('admin_pages.class_subjects.past')}
                      type={index === 0 && !item.valid_to ? 'success' : 'gray'}
                      size="sm"
                    />
                  </div>
                </div>
              ))}
            </div>
          </Section>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
