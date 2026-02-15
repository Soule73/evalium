import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Badge } from '@/Components';
import { BaseEntityList } from './BaseEntityList';
import { type EntityListConfig } from './types/listConfig';
import { type Enrollment, type ClassModel } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks';
import { type EntityListVariant } from './types/listConfig';

interface EnrollmentListProps {
  data: PaginationType<Enrollment>;
  variant?: EntityListVariant;
  showClassColumn?: boolean;
  classes?: ClassModel[];
  permissions?: {
    canView?: boolean;
  };
  onView?: (enrollment: Enrollment) => void;
}

/**
 * Unified EnrollmentList component for displaying enrollments
 *
 * Supports variants:
 * - admin: Shows student, class, enrolled_at, status with view action
 * - student: Shows academic year, class, level, dates, status (view only)
 */
export function EnrollmentList({
  data,
  variant = 'admin',
  showClassColumn = true,
  classes = [],
  permissions = {},
  onView,
}: EnrollmentListProps) {
  const { t } = useTranslations();

  const config: EntityListConfig<Enrollment> = useMemo(() => {
    const classFilterOptions = [
      { value: '', label: t('admin_pages.enrollments.all_classes') },
      ...classes.map((c) => ({
        value: c.id,
        label: `${c.name} ${c?.description ? `(${c.description})` : ''}`,
      })),
    ];

    const statusFilterOptions = [
      { value: '', label: t('admin_pages.enrollments.all_statuses') },
      { value: 'active', label: t('admin_pages.enrollments.status_active') },
      { value: 'withdrawn', label: t('admin_pages.enrollments.status_withdrawn') },
    ];

    const getStatusBadge = (status: string, currentVariant: EntityListVariant) => {
      if (currentVariant === 'student') {
        const statusMap: Record<string, { type: 'success' | 'info' | 'gray'; label: string }> = {
          active: { type: 'success', label: t('student_enrollment_pages.history.active') },
          completed: { type: 'info', label: t('student_enrollment_pages.history.completed') },
        };
        const cfg = statusMap[status] || { type: 'gray', label: status };
        return <Badge label={cfg.label} type={cfg.type} />;
      }

      const statusMap: Record<string, { type: 'success' | 'error' | 'warning' | 'info' | 'gray'; label: string }> = {
        active: { type: 'success', label: t('admin_pages.enrollments.status_active') },
        withdrawn: { type: 'gray', label: t('admin_pages.enrollments.status_withdrawn') },
      };

      const cfg = statusMap[status] || statusMap.active;
      return <Badge label={cfg.label} type={cfg.type} size="sm" />;
    };

    return {
      entity: 'enrollment',

      filters: [
        {
          key: 'class_id',
          labelKey: 'admin_pages.enrollments.class',
          type: 'select',
          options: classFilterOptions,
          conditional: (v) => v === 'admin',
        },
        {
          key: 'status',
          labelKey: 'admin_pages.enrollments.status',
          type: 'select',
          options: statusFilterOptions,
          conditional: (v) => v === 'admin',
        },
      ],

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
          conditional: (v) => v === 'admin' || v === 'teacher',
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
          conditional: () => showClassColumn,
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
                : t('student_enrollment_pages.history.not_available')}
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
      ],

      actions: [
        {
          labelKey: 'admin_pages.common.view',
          onClick: (item: Enrollment) =>
            onView?.(item) || router.visit(route('admin.enrollments.show', item.id)),
          color: 'secondary' as const,
          variant: 'outline' as const,
          conditional: (_item: Enrollment, v) => v === 'admin' && permissions.canView !== false,
        },
      ],
    };
  }, [variant, showClassColumn, classes, permissions.canView, onView, t]);

  return <BaseEntityList data={data} config={config} variant={variant} />;
}
