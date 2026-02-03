import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { ClassSubject, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Badge, Button, ConfirmationModal, DataTable, Section } from '@/Components';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  classSubjects: PaginationType<ClassSubject>;
  filters?: {
    search?: string;
    class_id?: string;
    subject_id?: string;
    active_only?: boolean;
  };
}

export default function ClassSubjectIndex({ classSubjects, auth }: Props) {
  const [archiveModal, setArchiveModal] = useState<{ isOpen: boolean; classSubject: ClassSubject | null }>({
    isOpen: false,
    classSubject: null,
  });

  const canCreate = hasPermission(auth.permissions, 'create class subjects');
  const canUpdate = hasPermission(auth.permissions, 'update class subjects');

  const handleCreate = () => {
    router.visit(route('admin.class-subjects.create'));
  };

  const handleView = (classSubject: ClassSubject) => {
    router.visit(route('admin.class-subjects.show', classSubject.id));
  };

  const handleReplaceTeacher = (classSubject: ClassSubject) => {
    router.visit(route('admin.class-subjects.replace-teacher', classSubject.id));
  };

  const handleUpdateCoefficient = (classSubject: ClassSubject) => {
    router.visit(route('admin.class-subjects.edit-coefficient', classSubject.id));
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

  const dataTableConfig: DataTableConfig<ClassSubject> = {
    columns: [
      {
        key: 'class',
        label: trans('admin_pages.class_subjects.class'),
        render: (classSubject) => (
          <div>
            <div className="font-medium text-gray-900">
              {classSubject.class?.display_name || classSubject.class?.name}
            </div>
            <div className="text-sm text-gray-500">
              {classSubject.class?.level?.name}
            </div>
          </div>
        ),
      },
      {
        key: 'subject',
        label: trans('admin_pages.class_subjects.subject'),
        render: (classSubject) => (
          <div className="flex items-center space-x-2">
            <Badge label={classSubject.subject?.code || ''} type="info" size="sm" />
            <span className="text-sm text-gray-900">{classSubject.subject?.name}</span>
          </div>
        ),
      },
      {
        key: 'teacher',
        label: trans('admin_pages.class_subjects.teacher'),
        render: (classSubject) => (
          <div>
            <div className="text-sm font-medium text-gray-900">
              {classSubject.teacher?.name}
            </div>
            <div className="text-xs text-gray-500">
              {classSubject.teacher?.email}
            </div>
          </div>
        ),
      },
      {
        key: 'coefficient',
        label: trans('admin_pages.class_subjects.coefficient'),
        render: (classSubject) => (
          <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
        ),
      },
      {
        key: 'semester',
        label: trans('admin_pages.class_subjects.semester'),
        render: (classSubject) => (
          <div className="text-sm text-gray-600">
            {classSubject.semester ? `S${classSubject.semester.order_number}` : trans('admin_pages.class_subjects.all_year')}
          </div>
        ),
      },
      {
        key: 'status',
        label: trans('admin_pages.common.status'),
        render: (classSubject) => {
          const isActive = !classSubject.valid_to;
          return (
            <Badge
              label={isActive ? trans('admin_pages.class_subjects.active') : trans('admin_pages.class_subjects.archived')}
              type={isActive ? 'success' : 'gray'}
              size="sm"
            />
          );
        },
      },
      {
        key: 'actions',
        label: trans('admin_pages.common.actions'),
        render: (classSubject) => (
          <div className="flex space-x-2">
            <Button size="sm" variant="outline" color="secondary" onClick={() => handleView(classSubject)}>
              {trans('admin_pages.common.view')}
            </Button>
            {canUpdate && !classSubject.valid_to && (
              <>
                <Button size="sm" variant="outline" color="primary" onClick={() => handleReplaceTeacher(classSubject)}>
                  {trans('admin_pages.class_subjects.replace_teacher')}
                </Button>
                <Button size="sm" variant="outline" color="warning" onClick={() => handleUpdateCoefficient(classSubject)}>
                  {trans('admin_pages.class_subjects.update_coefficient')}
                </Button>
                <Button size="sm" variant="outline" color="danger" onClick={() => handleArchiveClick(classSubject)}>
                  {trans('admin_pages.class_subjects.archive')}
                </Button>
              </>
            )}
          </div>
        ),
      },
    ],
    filters: [],
    emptyState: {
      title: trans('admin_pages.class_subjects.empty_title'),
      subtitle: trans('admin_pages.class_subjects.empty_subtitle'),
    },
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
        <DataTable data={classSubjects} config={dataTableConfig} />
      </Section>

      <ConfirmationModal
        isOpen={archiveModal.isOpen}
        onClose={() => setArchiveModal({ isOpen: false, classSubject: null })}
        onConfirm={handleArchiveConfirm}
        title={trans('admin_pages.class_subjects.archive_title')}
        message={trans('admin_pages.class_subjects.archive_message', {
          subject: archiveModal.classSubject?.subject?.name || '',
          class: archiveModal.classSubject?.class?.display_name || archiveModal.classSubject?.class?.name || ''
        })}
        confirmText={trans('admin_pages.class_subjects.archive_confirm')}
        cancelText={trans('admin_pages.common.cancel')}
        type="warning"
      />
    </AuthenticatedLayout>
  );
}
