import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { Subject, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, ConfirmationModal, Section } from '@/Components';
import { SubjectList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  subjects: PaginationType<Subject>;
}

export default function SubjectIndex({ subjects, auth }: Props) {
  const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; subject: Subject | null }>({
    isOpen: false,
    subject: null,
  });

  const canCreate = hasPermission(auth.permissions, 'create subjects');

  const handleCreate = () => {
    router.visit(route('admin.subjects.create'));
  };

  const handleDeleteClick = (subject: Subject) => {
    setDeleteModal({ isOpen: true, subject });
  };

  const handleDeleteConfirm = () => {
    if (deleteModal.subject) {
      router.delete(route('admin.subjects.destroy', deleteModal.subject.id), {
        onSuccess: () => {
          setDeleteModal({ isOpen: false, subject: null });
        },
      });
    }
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.subjects.title')}
      breadcrumb={breadcrumbs.admin.subjects()}
    >
      <Section
        title={trans('admin_pages.subjects.title')}
        subtitle={trans('admin_pages.subjects.subtitle')}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {trans('admin_pages.subjects.create')}
            </Button>
          )
        }
      >
        <SubjectList data={subjects} variant="admin" onDelete={handleDeleteClick} />
      </Section>

      <ConfirmationModal
        isOpen={deleteModal.isOpen}
        onClose={() => setDeleteModal({ isOpen: false, subject: null })}
        onConfirm={handleDeleteConfirm}
        title={trans('admin_pages.subjects.delete_title')}
        message={trans('admin_pages.subjects.delete_message', { name: deleteModal.subject?.name || '' })}
        confirmText={trans('admin_pages.common.delete')}
        cancelText={trans('admin_pages.common.cancel')}
        type="danger"
      />
    </AuthenticatedLayout>
  );
}
