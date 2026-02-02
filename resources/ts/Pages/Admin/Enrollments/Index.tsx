import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { Enrollment, PageProps } from '@/types';
import { breadcrumbs, trans, formatDate, hasPermission } from '@/utils';
import { Badge, Button, ConfirmationModal, DataTable, Section } from '@/Components';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  enrollments: PaginationType<Enrollment>;
  filters?: {
    search?: string;
    class_id?: string;
    status?: string;
  };
}

export default function EnrollmentIndex({ enrollments, auth }: Props) {
  const [withdrawModal, setWithdrawModal] = useState<{ isOpen: boolean; enrollment: Enrollment | null }>({
    isOpen: false,
    enrollment: null,
  });

  const canCreate = hasPermission(auth.permissions, 'create enrollments');
  const canUpdate = hasPermission(auth.permissions, 'update enrollments');

  const handleCreate = () => {
    router.visit(route('admin.enrollments.create'));
  };

  const handleView = (enrollment: Enrollment) => {
    router.visit(route('admin.enrollments.show', enrollment.id));
  };

  const handleTransfer = (enrollment: Enrollment) => {
    router.visit(route('admin.enrollments.transfer', enrollment.id));
  };

  const handleWithdrawClick = (enrollment: Enrollment) => {
    setWithdrawModal({ isOpen: true, enrollment });
  };

  const handleWithdrawConfirm = () => {
    if (withdrawModal.enrollment) {
      router.post(route('admin.enrollments.withdraw', withdrawModal.enrollment.id), {}, {
        onSuccess: () => {
          setWithdrawModal({ isOpen: false, enrollment: null });
        },
      });
    }
  };

  const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { type: 'success' | 'error' | 'warning' | 'info' | 'gray'; label: string }> = {
      active: { type: 'success', label: trans('admin_pages.enrollments.status_active') },
      transferred: { type: 'info', label: trans('admin_pages.enrollments.status_transferred') },
      withdrawn: { type: 'gray', label: trans('admin_pages.enrollments.status_withdrawn') },
    };

    const config = statusMap[status] || statusMap.active;
    return <Badge label={config.label} type={config.type} size="sm" />;
  };

  const dataTableConfig: DataTableConfig<Enrollment> = {
    columns: [
      {
        key: 'student',
        label: trans('admin_pages.enrollments.student'),
        render: (enrollment) => (
          <div>
            <div className="font-medium text-gray-900">{enrollment.student?.name}</div>
            <div className="text-sm text-gray-500">{enrollment.student?.email}</div>
          </div>
        ),
      },
      {
        key: 'class',
        label: trans('admin_pages.enrollments.class'),
        render: (enrollment) => (
          <div>
            <div className="font-medium text-gray-900">
              {enrollment.class?.display_name || enrollment.class?.name}
            </div>
            <div className="text-sm text-gray-500">
              {enrollment.class?.level?.name} - {enrollment.class?.academic_year?.name}
            </div>
          </div>
        ),
      },
      {
        key: 'enrolled_at',
        label: trans('admin_pages.enrollments.enrolled_at'),
        render: (enrollment) => (
          <div className="text-sm text-gray-600">
            {formatDate(enrollment.enrolled_date)}
          </div>
        ),
      },
      {
        key: 'status',
        label: trans('admin_pages.enrollments.status'),
        render: (enrollment) => getStatusBadge(enrollment.status),
      },
      {
        key: 'actions',
        label: trans('admin_pages.common.actions'),
        render: (enrollment) => (
          <div className="flex space-x-2">
            <Button size="sm" variant="outline" color="secondary" onClick={() => handleView(enrollment)}>
              {trans('admin_pages.common.view')}
            </Button>
            {canUpdate && enrollment.status === 'active' && (
              <>
                <Button size="sm" variant="outline" color="primary" onClick={() => handleTransfer(enrollment)}>
                  {trans('admin_pages.enrollments.transfer')}
                </Button>
                <Button size="sm" variant="outline" color="danger" onClick={() => handleWithdrawClick(enrollment)}>
                  {trans('admin_pages.enrollments.withdraw')}
                </Button>
              </>
            )}
          </div>
        ),
      },
    ],
    filters: [],
    emptyState: {
      title: trans('admin_pages.enrollments.empty_title'),
      subtitle: trans('admin_pages.enrollments.empty_subtitle'),
    },
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.enrollments.title')}
      breadcrumb={breadcrumbs.admin.enrollments()}
    >
      <Section
        title={trans('admin_pages.enrollments.title')}
        subtitle={trans('admin_pages.enrollments.subtitle')}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {trans('admin_pages.enrollments.create')}
            </Button>
          )
        }
      >
        <DataTable data={enrollments} config={dataTableConfig} />
      </Section>

      <ConfirmationModal
        isOpen={withdrawModal.isOpen}
        onClose={() => setWithdrawModal({ isOpen: false, enrollment: null })}
        onConfirm={handleWithdrawConfirm}
        title={trans('admin_pages.enrollments.withdraw_title')}
        message={trans('admin_pages.enrollments.withdraw_message', {
          student: withdrawModal.enrollment?.student?.name || '',
          class: withdrawModal.enrollment?.class?.display_name || withdrawModal.enrollment?.class?.name || ''
        })}
        confirmText={trans('admin_pages.enrollments.withdraw_confirm')}
        cancelText={trans('admin_pages.common.cancel')}
        type="warning"
      />
    </AuthenticatedLayout>
  );
}
