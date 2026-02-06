import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { ClassModel, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, ConfirmationModal, Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  classes: PaginationType<ClassModel>;
  filters?: {
    search?: string;
    academic_year_id?: string;
    level_id?: string;
  };
}

export default function ClassIndex({ classes, auth }: Props) {
  const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; classItem: ClassModel | null }>({
    isOpen: false,
    classItem: null,
  });

  const canCreate = hasPermission(auth.permissions, 'create classes');

  const handleCreate = () => {
    router.visit(route('admin.classes.create'));
  };

  const handleDeleteClick = (classItem: ClassModel) => {
    setDeleteModal({ isOpen: true, classItem });
  };

  const handleDeleteConfirm = () => {
    if (deleteModal.classItem) {
      router.delete(route('admin.classes.destroy', deleteModal.classItem.id), {
        onSuccess: () => {
          setDeleteModal({ isOpen: false, classItem: null });
        },
      });
    }
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.classes.title')}
      breadcrumb={breadcrumbs.admin.classes()}
    >
      <Section
        title={trans('admin_pages.classes.title')}
        subtitle={trans('admin_pages.classes.subtitle')}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {trans('admin_pages.classes.create')}
            </Button>
          )
        }
      >
        <ClassList data={classes} variant="admin" onDelete={handleDeleteClick} />
      </Section>

      <ConfirmationModal
        isOpen={deleteModal.isOpen}
        onClose={() => setDeleteModal({ isOpen: false, classItem: null })}
        onConfirm={handleDeleteConfirm}
        title={trans('admin_pages.classes.delete_title')}
        message={trans('admin_pages.classes.delete_message', { name: deleteModal.classItem?.name || '' })}
        confirmText={trans('admin_pages.common.delete')}
        cancelText={trans('admin_pages.common.cancel')}
        type="danger"
      />
    </AuthenticatedLayout>
  );
}
