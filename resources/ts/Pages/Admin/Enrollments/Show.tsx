import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, ClassModel, PageProps, PaginationType, SubjectGrade, OverallStats } from '@/types';
import { breadcrumbs, trans, formatDate, hasPermission } from '@/utils';
import { Button, Section, Badge, Stat } from '@/Components';
import { SubjectGradeList } from '@/Components/shared/lists/SubjectGradeList';
import { TransferEnrollmentModal, WithdrawEnrollmentModal } from '@/Components/features';
import { route } from 'ziggy-js';
import {
  UserIcon,
  AcademicCapIcon,
  CalendarIcon,
  ArrowPathIcon,
} from '@heroicons/react/24/outline';

interface Props extends PageProps {
  enrollment: Enrollment;
  classes: ClassModel[];
  subjects: PaginationType<SubjectGrade>;
  overallStats: OverallStats;
}
export default function EnrollmentShow({ enrollment, classes, subjects, overallStats, auth }: Props) {
  const canUpdate = hasPermission(auth.permissions, 'update enrollments');

  const [transferModalOpen, setTransferModalOpen] = useState(false);
  const [withdrawModalOpen, setWithdrawModalOpen] = useState(false);

  const handleBack = () => {
    router.visit(route('admin.enrollments.index'));
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

  const levelNameDescription = `${enrollment.class?.level?.name} (${enrollment.class?.level?.description})`;

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.enrollments.show_title')}
      breadcrumb={breadcrumbs.admin.showEnrollment(enrollment)}
    >
      <div className="space-y-6">
        <Section
          title={trans('admin_pages.enrollments.show_title')}
          subtitle={trans('admin_pages.enrollments.show_subtitle')}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {trans('admin_pages.common.back')}
              </Button>
              {canUpdate && enrollment.status === 'active' && (
                <>
                  <Button size="sm" variant="solid" color="primary" onClick={() => setTransferModalOpen(true)}>
                    {trans('admin_pages.enrollments.transfer')}
                  </Button>
                  <Button size="sm" variant="outline" color="danger" onClick={() => setWithdrawModalOpen(true)}>
                    {trans('admin_pages.enrollments.withdraw')}
                  </Button>
                </>
              )}
            </div>
          }
        >
          <Stat.Group columns={2}>
            <Stat.Item
              icon={UserIcon}
              title={trans('admin_pages.enrollments.student')}
              value={
                <span className="text-sm font-semibold text-gray-900">
                  {enrollment.student?.name}
                </span>
              }
              description={enrollment.student?.email}
            />

            <Stat.Item
              icon={AcademicCapIcon}
              title={trans('admin_pages.enrollments.class')}
              value={
                <span className="text-sm font-semibold text-gray-900">
                  {enrollment.class?.name}
                </span>
              }
              description={levelNameDescription}
            />

            <Stat.Item
              icon={CalendarIcon}
              title={trans('admin_pages.enrollments.enrolled_at')}
              value={
                <span className="text-sm text-gray-900">
                  {formatDate(enrollment.enrolled_at)}
                </span>
              }
            />

            <Stat.Item
              icon={ArrowPathIcon}
              title={trans('admin_pages.enrollments.status')}
              value={getStatusBadge(enrollment.status)}
            />
          </Stat.Group>

          {enrollment.status === 'transferred' && enrollment.left_date && (
            <div className="mt-6 pt-6 border-t border-gray-200">
              <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div className="text-sm font-medium text-blue-900 mb-2">
                  {trans('admin_pages.enrollments.transfer_info')}
                </div>
                <div className="text-sm text-blue-800">
                  {trans('admin_pages.enrollments.transferred_at')}: {formatDate(enrollment.left_date)}
                </div>
              </div>
            </div>
          )}

          {enrollment.status === 'withdrawn' && enrollment.left_date && (
            <div className="mt-6 pt-6 border-t border-gray-200">
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div className="text-sm font-medium text-gray-900 mb-2">
                  {trans('admin_pages.enrollments.withdrawal_info')}
                </div>
                <div className="text-sm text-gray-700">
                  {trans('admin_pages.enrollments.withdrawn_at')}: {formatDate(enrollment.left_date)}
                </div>
              </div>
            </div>
          )}
        </Section>

        <SubjectGradeList subjects={subjects} overallStats={overallStats} variant="admin" />
      </div>

      <TransferEnrollmentModal
        isOpen={transferModalOpen}
        onClose={() => setTransferModalOpen(false)}
        enrollment={enrollment}
        classes={classes}
      />

      <WithdrawEnrollmentModal
        isOpen={withdrawModalOpen}
        onClose={() => setWithdrawModalOpen(false)}
        enrollment={enrollment}
      />
    </AuthenticatedLayout>
  );
}
