import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { ClassModel, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Badge, Button, ConfirmationModal, DataTable, Section } from '@/Components';
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
  const canUpdate = hasPermission(auth.permissions, 'update classes');
  const canDelete = hasPermission(auth.permissions, 'delete classes');

  const handleCreate = () => {
    router.visit(route('admin.classes.create'));
  };

  const handleView = (classItem: ClassModel) => {
    router.visit(route('admin.classes.show', classItem.id));
  };

  const handleEdit = (classItem: ClassModel) => {
    router.visit(route('admin.classes.edit', classItem.id));
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

  const dataTableConfig: DataTableConfig<ClassModel> = {
    columns: [
      {
        key: 'name',
        label: trans('admin_pages.classes.name'),
        render: (classItem) => (
          <div>
            <div className="font-medium text-gray-900">{classItem.display_name || classItem.name}</div>
            <div className="text-sm text-gray-500">
              {classItem.level?.name} - {classItem.academic_year?.name}
            </div>
          </div>
        ),
      },
      {
        key: 'students',
        label: trans('admin_pages.classes.students'),
        render: (classItem) => {
          const activeCount = classItem.active_enrollments_count || 0;
          const maxStudents = classItem.max_students;
          const percentage = maxStudents > 0 ? (activeCount / maxStudents) * 100 : 0;

          return (
            <div className="flex items-center space-x-2">
              <span className="text-sm font-medium text-gray-900">
                {activeCount} / {maxStudents}
              </span>
              {percentage >= 90 && (
                <Badge label={trans('admin_pages.classes.full')} type="warning" size="sm" />
              )}
            </div>
          );
        },
      },
      {
        key: 'subjects',
        label: trans('admin_pages.classes.subjects'),
        render: (classItem) => (
          <div className="text-sm text-gray-600">
            {classItem.subjects_count || 0}
          </div>
        ),
      },
      {
        key: 'actions',
        label: trans('admin_pages.common.actions'),
        render: (classItem) => (
          <div className="flex space-x-2">
            <Button size="sm" variant="outline" color="secondary" onClick={() => handleView(classItem)}>
              {trans('admin_pages.common.view')}
            </Button>
            {canUpdate && (
              <Button size="sm" variant="outline" color="primary" onClick={() => handleEdit(classItem)}>
                {trans('admin_pages.common.edit')}
              </Button>
            )}
            {canDelete && (
              <Button size="sm" variant="outline" color="danger" onClick={() => handleDeleteClick(classItem)}>
                {trans('admin_pages.common.delete')}
              </Button>
            )}
          </div>
        ),
      },
    ],
    filters: [],
    emptyState: {
      title: trans('admin_pages.classes.empty_title'),
      subtitle: trans('admin_pages.classes.empty_subtitle'),
    },
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
        <DataTable data={classes} config={dataTableConfig} />
      </Section>

      <ConfirmationModal
        isOpen={deleteModal.isOpen}
        onClose={() => setDeleteModal({ isOpen: false, classItem: null })}
        onConfirm={handleDeleteConfirm}
        title={trans('admin_pages.classes.delete_title')}
        message={trans('admin_pages.classes.delete_message', { name: deleteModal.classItem?.display_name || deleteModal.classItem?.name || '' })}
        confirmText={trans('admin_pages.common.delete')}
        cancelText={trans('admin_pages.common.cancel')}
        type="danger"
      />
    </AuthenticatedLayout>
  );
}
