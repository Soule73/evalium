import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { ClassSubject, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, ConfirmationModal, Section } from '@/Components';
import { ClassSubjectList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  classSubjects: PaginationType<ClassSubject>;
}

export default function ClassSubjectIndex({ classSubjects, auth }: Props) {
  const [archiveModal, setArchiveModal] = useState<{ isOpen: boolean; classSubject: ClassSubject | null }>({
    isOpen: false,
    classSubject: null,
  });

  const canCreate = hasPermission(auth.permissions, 'create class subjects');

  const handleCreate = () => {
    router.visit(route('admin.class-subjects.create'));
  };

  const handleArchiveClick = (classSubject: ClassSubject) => {
    setArchiveModal({ isOpen: true, classSubject });
  };

  const handleArchiveConfirm = () => {
    if (archiveModal.classSubject) {
      router.post(route('admin.class-subjects.archive', archiveModal.classSubject.id), {}, {
        onSuccess: () => {
          setArchiveModal({ isOpen: false, classSubject: null });
        },
      });
    }
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.class_subjects.title')}
      breadcrumb={breadcrumbs.admin.classSubjects()}
    >
      <Section
        title={trans('admin_pages.class_subjects.title')}
        subtitle={trans('admin_pages.class_subjects.subtitle')}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {trans('admin_pages.class_subjects.create')}
            </Button>
          )
        }
      >
        <ClassSubjectList
          data={classSubjects}
          variant="admin"
          onArchive={handleArchiveClick}
        />
      </Section>

      <ConfirmationModal
        isOpen={archiveModal.isOpen}
        onClose={() => setArchiveModal({ isOpen: false, classSubject: null })}
        onConfirm={handleArchiveConfirm}
        title={trans('admin_pages.class_subjects.archive_title')}
        message={trans('admin_pages.class_subjects.archive_message', {
          subject: archiveModal.classSubject?.subject?.name || '',
          class: archiveModal.classSubject?.class?.name || ''
        })}
        confirmText={trans('admin_pages.class_subjects.archive_confirm')}
        cancelText={trans('admin_pages.common.cancel')}
        type="warning"
      />
    </AuthenticatedLayout>
  );
}
