import { Badge } from '@/Components';
import { Enrollment } from '@/types';
import { trans, formatDate } from '@/utils';
import { UserIcon, AcademicCapIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface EnrollmentCardProps {
  enrollment: Enrollment;
  onClick?: () => void;
}

export function EnrollmentCard({ enrollment, onClick }: EnrollmentCardProps) {
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
    <div
      onClick={onClick}
      className={`bg-white rounded-lg border border-gray-200 p-5 shadow-sm transition-all ${onClick ? 'hover:border-primary-300 hover:shadow-md cursor-pointer' : ''
        }`}
    >
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center space-x-2">
          <UserIcon className="w-5 h-5 text-gray-400" />
          <h3 className="text-lg font-semibold text-gray-900">
            {enrollment.student?.name}
          </h3>
        </div>
        {getStatusBadge(enrollment.status)}
      </div>

      <div className="space-y-3">
        <div className="flex items-start space-x-2">
          <AcademicCapIcon className="w-4 h-4 text-gray-400 mt-0.5" />
          <div>
            <div className="text-sm font-medium text-gray-900">
              {enrollment.class?.display_name || enrollment.class?.name}
            </div>
            <div className="text-xs text-gray-500">
              {enrollment.class?.level?.name}
            </div>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          <CalendarIcon className="w-4 h-4 text-gray-400" />
          <div className="text-sm text-gray-600">
            {trans('admin_pages.enrollments.enrolled_on')}: {formatDate(enrollment.enrolled_at)}
          </div>
        </div>

        {enrollment.status === 'withdrawn' && enrollment.left_date && (
          <div className="pt-3 border-t border-gray-100">
            <div className="text-xs text-gray-500">
              {trans('admin_pages.enrollments.withdrawn_on')}: {formatDate(enrollment.left_date)}
            </div>
          </div>
        )}

        {enrollment.status === 'transferred' && enrollment.left_date && (
          <div className="pt-3 border-t border-gray-100">
            <div className="text-xs text-blue-600">
              {trans('admin_pages.enrollments.transferred_on')}: {formatDate(enrollment.left_date)}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
