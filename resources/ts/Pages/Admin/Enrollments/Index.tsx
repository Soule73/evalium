import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { Enrollment, PageProps } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, ConfirmationModal, Section } from '@/Components';
import { EnrollmentList } from '@/Components/shared/lists';
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
  const [withdrawModal, setWithdrawModal] = useState<{
    isOpen: boolean;
    enrollment: Enrollment | null;
  }>({
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
      router.post(
        route('admin.enrollments.withdraw', withdrawModal.enrollment.id),
        {},
        {
          onSuccess: () => {
            setWithdrawModal({ isOpen: false, enrollment: null });
          },
        }
      );
    }
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
            <Button
              size="sm"
              variant="solid"
              color="primary"
              onClick={handleCreate}
            >
              {trans('admin_pages.enrollments.create')}
            </Button>
          )
        }
      >
        <EnrollmentList
          data={enrollments}
          variant="admin"
          showActions={true}
          permissions={{
            canView: true,
            canUpdate: canUpdate,
          }}
          onView={handleView}
          onTransfer={handleTransfer}
          onWithdraw={handleWithdrawClick}
        />
      </Section>

      <ConfirmationModal
        isOpen={withdrawModal.isOpen}
        onClose={() => setWithdrawModal({ isOpen: false, enrollment: null })}
        onConfirm={handleWithdrawConfirm}
        title={trans('admin_pages.enrollments.withdraw_title')}
        message={trans('admin_pages.enrollments.withdraw_message', {
          student: withdrawModal.enrollment?.student?.name || '',
          class:
            withdrawModal.enrollment?.class?.display_name ||
            withdrawModal.enrollment?.class?.name ||
            '',
        })}
        confirmText={trans('admin_pages.enrollments.withdraw_confirm')}
        cancelText={trans('admin_pages.common.cancel')}
        type="warning"
      />
    </AuthenticatedLayout>
  );
}
