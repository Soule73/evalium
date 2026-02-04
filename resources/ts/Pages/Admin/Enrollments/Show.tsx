import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, PageProps } from '@/types';
import { breadcrumbs, trans, formatDate, hasPermission } from '@/utils';
import { Button, Section, Badge } from '@/Components';
import { route } from 'ziggy-js';
import { UserIcon, AcademicCapIcon, CalendarIcon, ArrowPathIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  enrollment: Enrollment;
}

export default function EnrollmentShow({ enrollment, auth }: Props) {
  const canUpdate = hasPermission(auth.permissions, 'update enrollments');

  const handleTransfer = () => {
    router.visit(route('admin.enrollments.transfer', enrollment.id));
  };

  const handleWithdraw = () => {
    if (confirm(trans('admin_pages.enrollments.withdraw_confirm_message'))) {
      router.post(route('admin.enrollments.withdraw', enrollment.id));
    }
  };

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
                  <Button size="sm" variant="solid" color="primary" onClick={handleTransfer}>
                    {trans('admin_pages.enrollments.transfer')}
                  </Button>
                  <Button size="sm" variant="outline" color="danger" onClick={handleWithdraw}>
                    {trans('admin_pages.enrollments.withdraw')}
                  </Button>
                </>
              )}
            </div>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="flex items-start space-x-3">
              <UserIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.enrollments.student')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {enrollment.student?.name}
                </div>
                <div className="text-xs text-gray-500">
                  {enrollment.student?.email}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.enrollments.class')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {enrollment.class?.display_name || enrollment.class?.name}
                </div>
                <div className="text-xs text-gray-500">
                  {enrollment.class?.level?.name} - {enrollment.class?.academic_year?.name}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <CalendarIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.enrollments.enrolled_at')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {formatDate(enrollment.enrolled_at)}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <ArrowPathIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.enrollments.status')}
                </div>
                <div className="mt-1">
                  {getStatusBadge(enrollment.status)}
                </div>
              </div>
            </div>
          </div>

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
      </div>
    </AuthenticatedLayout>
  );
}
