import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Subject, ClassSubject, PageProps, PaginationType } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section, Badge } from '@/Components';
import { ClassSubjectList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';
import { AcademicCapIcon, BookOpenIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  subject: Subject;
  classSubjects: PaginationType<ClassSubject>;
  classSubjectsFilters?: {
    search?: string;
  };
}

export default function SubjectShow({ subject, classSubjects, auth }: Props) {
  const canUpdate = hasPermission(auth.permissions, 'update subjects');
  const canDelete = hasPermission(auth.permissions, 'delete subjects');

  const handleEdit = () => {
    router.visit(route('admin.subjects.edit', subject.id));
  };

  const handleDelete = () => {
    if (confirm(trans('admin_pages.subjects.delete_confirm'))) {
      router.delete(route('admin.subjects.destroy', subject.id));
    }
  };

  const handleBack = () => {
    router.visit(route('admin.subjects.index'));
  };

  const handleClassClick = (classSubject: ClassSubject) => {
    if (classSubject.class) {
      router.visit(route('admin.classes.show', classSubject.class.id));
    }
  };

  return (
    <AuthenticatedLayout
      title={subject.name}
      breadcrumb={breadcrumbs.admin.showSubject(subject)}
    >
      <div className="space-y-6">
        <Section
          title={subject.name}
          subtitle={trans('admin_pages.subjects.show_subtitle')}
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
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="flex items-start space-x-3">
              <BookOpenIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.subjects.code')}
                </div>
                <div className="mt-1">
                  <Badge label={subject.code} type="info" size="sm" />
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.subjects.level')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {subject.level?.name || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <BookOpenIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.subjects.classes_count')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {classSubjects.total || 0}
                </div>
              </div>
            </div>
          </div>

          {subject.description && (
            <div className="mt-6 pt-6 border-t border-gray-200">
              <div className="text-sm font-medium text-gray-500 mb-2">
                {trans('admin_pages.subjects.description')}
              </div>
              <div className="text-sm text-gray-900">{subject.description}</div>
            </div>
          )}
        </Section>

        <Section
          title={trans('admin_pages.subjects.classes_section')}
          subtitle={trans('admin_pages.subjects.classes_section_subtitle')}
        >
          <ClassSubjectList data={classSubjects} onClassClick={handleClassClick} />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
