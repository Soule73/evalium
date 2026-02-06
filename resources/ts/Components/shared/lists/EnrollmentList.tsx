import { Button, Badge } from '@/Components';
import { BaseEntityList } from './BaseEntityList';
import { EntityListConfig } from './types/listConfig';
import { Enrollment } from '@/types';
import { PaginationType } from '@/types/datatable';
import { trans, formatDate } from '@/utils';
import { EntityListVariant } from './types/listConfig';

interface EnrollmentListProps {
  data: PaginationType<Enrollment>;
  variant?: EntityListVariant;
  showActions?: boolean;
  permissions?: {
    canView?: boolean;
    canUpdate?: boolean;
  };
  onView?: (enrollment: Enrollment) => void;
  onTransfer?: (enrollment: Enrollment) => void;
  onWithdraw?: (enrollment: Enrollment) => void;
}

export function EnrollmentList({
  data,
  variant = 'admin',
  showActions = true,
  permissions = {},
  onView,
  onTransfer,
  onWithdraw,
}: EnrollmentListProps) {
  const getStatusBadge = (status: string, currentVariant: EntityListVariant) => {
    if (currentVariant === 'student') {
      const statusMap: Record<string, { type: 'success' | 'info' | 'gray'; label: string }> = {
        active: { type: 'success', label: trans('student_enrollment_pages.history.active') },
        completed: { type: 'info', label: trans('student_enrollment_pages.history.completed') },
      };
      const config = statusMap[status] || { type: 'gray', label: status };
      return <Badge label={config.label} type={config.type} />;
    }

    const statusMap: Record<string, { type: 'success' | 'error' | 'warning' | 'info' | 'gray'; label: string }> = {
      active: { type: 'success', label: trans('admin_pages.enrollments.status_active') },
      transferred: { type: 'info', label: trans('admin_pages.enrollments.status_transferred') },
      withdrawn: { type: 'gray', label: trans('admin_pages.enrollments.status_withdrawn') },
    };

    const config = statusMap[status] || statusMap.active;
    return <Badge label={config.label} type={config.type} size="sm" />;
  };

  const config: EntityListConfig<Enrollment> = {
    entity: 'enrollment',
    columns: [
      {
        key: 'academic_year',
        labelKey: 'student_enrollment_pages.history.academic_year',
        render: (enrollment) => (
          <span className="font-medium text-gray-900">
            {enrollment.class?.academic_year?.name || '-'}
          </span>
        ),
        conditional: (v) => v === 'student',
      },
      {
        key: 'student',
        labelKey: 'admin_pages.enrollments.student',
        render: (enrollment) => (
          <div>
            <div className="font-medium text-gray-900">{enrollment.student?.name}</div>
            <div className="text-sm text-gray-500">{enrollment.student?.email}</div>
          </div>
        ),
        conditional: (v) => v === 'admin',
      },
      {
        key: 'class',
        labelKey:
          variant === 'student'
            ? 'student_enrollment_pages.history.class'
            : 'admin_pages.enrollments.class',
        render: (enrollment, currentVariant) => {
          if (currentVariant === 'student') {
            return <span className="text-gray-700">{enrollment.class?.name || '-'}</span>;
          }
          const levelNameDescription = `${enrollment.class?.level?.name} (${enrollment.class?.level?.description})`;
          return (
            <div>
              <div className="font-medium text-gray-900">
                {enrollment.class?.name}
              </div>
              <div className="text-sm text-gray-500">
                {levelNameDescription}
              </div>
            </div>
          );
        },
      },
      {
        key: 'level',
        labelKey: 'student_enrollment_pages.history.level',
        render: (enrollment) => (
          <span className="text-gray-700">{enrollment.class?.level?.name || '-'}</span>
        ),
        conditional: (v) => v === 'student',
      },
      {
        key: 'enrolled_at',
        labelKey:
          variant === 'student'
            ? 'student_enrollment_pages.history.enrolled_on'
            : 'admin_pages.enrollments.enrolled_at',
        render: (enrollment, currentVariant) => (
          <div className={currentVariant === 'student' ? 'text-gray-700' : 'text-sm text-gray-600'}>
            {formatDate(enrollment.enrolled_at)}
          </div>
        ),
      },
      {
        key: 'completed_at',
        labelKey: 'student_enrollment_pages.history.completed_on',
        render: (enrollment) => (
          <span className="text-gray-700">
            {enrollment.status === 'completed'
              ? formatDate(enrollment.enrolled_at)
              : trans('student_enrollment_pages.history.not_available')}
          </span>
        ),
        conditional: (v) => v === 'student',
      },
      {
        key: 'status',
        labelKey:
          variant === 'student'
            ? 'student_enrollment_pages.history.status'
            : 'admin_pages.enrollments.status',
        render: (enrollment, currentVariant) =>
          getStatusBadge(enrollment.status, currentVariant || variant),
      },
      {
        key: 'actions',
        labelKey: 'admin_pages.common.actions',
        render: (enrollment) => (
          <div className="flex space-x-2">
            {permissions.canView && onView && (
              <Button
                size="sm"
                variant="outline"
                color="secondary"
                onClick={() => onView(enrollment)}
              >
                {trans('admin_pages.common.view')}
              </Button>
            )}
            {permissions.canUpdate && enrollment.status === 'active' && (
              <>
                {onTransfer && (
                  <Button
                    size="sm"
                    variant="outline"
                    color="primary"
                    onClick={() => onTransfer(enrollment)}
                  >
                    {trans('admin_pages.enrollments.transfer')}
                  </Button>
                )}
                {onWithdraw && (
                  <Button
                    size="sm"
                    variant="outline"
                    color="danger"
                    onClick={() => onWithdraw(enrollment)}
                  >
                    {trans('admin_pages.enrollments.withdraw')}
                  </Button>
                )}
              </>
            )}
          </div>
        ),
        conditional: (v) => v === 'admin' && showActions,
      },
    ],
  };

  return <BaseEntityList data={data} config={config} variant={variant} />;
}
