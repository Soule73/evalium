import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Enrollment, type ClassModel, type PageProps, type PaginationType, type SubjectGrade, type OverallStats } from '@/types';
import { formatDate, hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
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
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const canUpdate = hasPermission(auth.permissions, 'update enrollments');

  const [transferModalOpen, setTransferModalOpen] = useState(false);
  const [withdrawModalOpen, setWithdrawModalOpen] = useState(false);

  const translations = useMemo(() => ({
    showTitle: t('admin_pages.enrollments.show_title'),
    showSubtitle: t('admin_pages.enrollments.show_subtitle'),
    back: t('admin_pages.common.back'),
    transfer: t('admin_pages.enrollments.transfer'),
    withdraw: t('admin_pages.enrollments.withdraw'),
    student: t('admin_pages.enrollments.student'),
    class: t('admin_pages.enrollments.class'),
    enrolledAt: t('admin_pages.enrollments.enrolled_at'),
    status: t('admin_pages.enrollments.status'),
    statusActive: t('admin_pages.enrollments.status_active'),
    statusTransferred: t('admin_pages.enrollments.status_transferred'),
    statusWithdrawn: t('admin_pages.enrollments.status_withdrawn'),
    transferInfo: t('admin_pages.enrollments.transfer_info'),
    transferredAt: t('admin_pages.enrollments.transferred_at'),
    withdrawalInfo: t('admin_pages.enrollments.withdrawal_info'),
    withdrawnAt: t('admin_pages.enrollments.withdrawn_at'),
  }), [t]);

  const handleBack = () => {
    router.visit(route('admin.enrollments.index'));
  };

  const getStatusBadge = (status: string) => {
    const statusMap: Record<string, { type: 'success' | 'error' | 'warning' | 'info' | 'gray'; label: string }> = {
      active: { type: 'success', label: translations.statusActive },
      transferred: { type: 'info', label: translations.statusTransferred },
      withdrawn: { type: 'gray', label: translations.statusWithdrawn },
    };

    const config = statusMap[status] || statusMap.active;
    return <Badge label={config.label} type={config.type} size="sm" />;
  };

  const levelNameDescription = `${enrollment.class?.level?.name} (${enrollment.class?.level?.description})`;

  return (
    <AuthenticatedLayout
      title={translations.showTitle}
      breadcrumb={breadcrumbs.admin.showEnrollment(enrollment)}
    >
      <div className="space-y-6">
        <Section
          title={translations.showTitle}
          subtitle={translations.showSubtitle}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {translations.back}
              </Button>
              {canUpdate && enrollment.status === 'active' && (
                <>
                  <Button size="sm" variant="solid" color="primary" onClick={() => setTransferModalOpen(true)}>
                    {translations.transfer}
                  </Button>
                  <Button size="sm" variant="outline" color="danger" onClick={() => setWithdrawModalOpen(true)}>
                    {translations.withdraw}
                  </Button>
                </>
              )}
            </div>
          }
        >
          <Stat.Group columns={2}>
            <Stat.Item
              icon={UserIcon}
              title={translations.student}
              value={
                <span className="text-sm font-semibold text-gray-900">
                  {enrollment.student?.name}
                </span>
              }
              description={enrollment.student?.email}
            />

            <Stat.Item
              icon={AcademicCapIcon}
              title={translations.class}
              value={
                <span className="text-sm font-semibold text-gray-900">
                  {enrollment.class?.name}
                </span>
              }
              description={levelNameDescription}
            />

            <Stat.Item
              icon={CalendarIcon}
              title={translations.enrolledAt}
              value={
                <span className="text-sm text-gray-900">
                  {formatDate(enrollment.enrolled_at)}
                </span>
              }
            />

            <Stat.Item
              icon={ArrowPathIcon}
              title={translations.status}
              value={getStatusBadge(enrollment.status)}
            />
          </Stat.Group>

          {enrollment.status === 'transferred' && enrollment.left_date && (
            <div className="mt-6 pt-6 border-t border-gray-200">
              <div className="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <div className="text-sm font-medium text-indigo-900 mb-2">
                  {translations.transferInfo}
                </div>
                <div className="text-sm text-indigo-800">
                  {translations.transferredAt}: {formatDate(enrollment.left_date)}
                </div>
              </div>
            </div>
          )}

          {enrollment.status === 'withdrawn' && enrollment.left_date && (
            <div className="mt-6 pt-6 border-t border-gray-200">
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                <div className="text-sm font-medium text-gray-900 mb-2">
                  {translations.withdrawalInfo}
                </div>
                <div className="text-sm text-gray-700">
                  {translations.withdrawnAt}: {formatDate(enrollment.left_date)}
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
