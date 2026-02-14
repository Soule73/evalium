import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Subject, type ClassSubject, type PageProps, type PaginationType } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section, ConfirmationModal } from '@/Components';
import { Badge, Stat } from '@examena/ui';
import { SubjectList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';
import { AcademicCapIcon, BookOpenIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  subject: Subject;
  classSubjects: PaginationType<ClassSubject>;
}

export default function SubjectShow({ subject, classSubjects, auth }: Props) {
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);

  const canUpdate = hasPermission(auth.permissions, 'update subjects');
  const canDelete = hasPermission(auth.permissions, 'delete subjects') && subject.can_delete;

  const handleEdit = () => {
    router.visit(route('admin.subjects.edit', subject.id));
  };

  const handleDeleteClick = () => {
    setIsDeleteModalOpen(true);
  };

  const handleDeleteConfirm = () => {
    router.delete(route('admin.subjects.destroy', subject.id));
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
                <Button size="sm" variant="outline" color="danger" onClick={handleDeleteClick}>
                  {trans('admin_pages.common.delete')}
                </Button>
              )}
            </div>
          }
        >
          <Stat.Group columns={3}>
            <Stat.Item
              icon={BookOpenIcon}
              title={trans('admin_pages.subjects.code')}
              value={<Badge label={subject.code} type="info" size="sm" />}
            />
            <Stat.Item
              icon={AcademicCapIcon}
              title={trans('admin_pages.subjects.level')}
              value={<span className="text-sm text-gray-900">{subject.level?.name || '-'}</span>}
            />
            <Stat.Item
              icon={BookOpenIcon}
              title={trans('admin_pages.subjects.classes_count')}
              value={<span className="text-sm font-semibold text-gray-900">{classSubjects.total || 0}</span>}
            />
          </Stat.Group>

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
          <SubjectList data={classSubjects} variant="class-assignment" onClassClick={handleClassClick} />
        </Section>
      </div>

      <ConfirmationModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        title={trans('admin_pages.subjects.delete_title')}
        message={trans('admin_pages.subjects.delete_message', { name: subject.name })}
        confirmText={trans('admin_pages.common.delete')}
        cancelText={trans('admin_pages.common.cancel')}
        type="danger"
      />
    </AuthenticatedLayout>
  );
}
