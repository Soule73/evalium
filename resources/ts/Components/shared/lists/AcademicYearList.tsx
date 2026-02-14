import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type AcademicYear } from '@/types';
import { Badge } from '@examena/ui';
import { CheckCircleIcon, ArchiveBoxIcon } from '@heroicons/react/24/outline';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface AcademicYearListProps {
  data: PaginationType<AcademicYear>;
  showPagination?: boolean;
  onSetCurrent?: (year: AcademicYear) => void;
  onDelete?: (year: AcademicYear) => void;
}

/**
 * Unified AcademicYearList component for displaying academic years.
 *
 * Displays all academic years with status indicators, date ranges,
 * and contextual actions (view, activate, delete).
 */
export function AcademicYearList({
  data,
  showPagination = true,
  onSetCurrent,
  onDelete,
}: AcademicYearListProps) {
  const { t } = useTranslations();

  const config: EntityListConfig<AcademicYear> = useMemo(() => ({
    entity: 'academic-year',

    columns: [
      {
        key: 'name',
        labelKey: 'admin_pages.academic_years.name',
        render: (year) => (
          <div className="flex items-center space-x-3">
            {year.is_current ? (
              <CheckCircleIcon className="w-5 h-5 text-green-500" />
            ) : (
              <ArchiveBoxIcon className="w-5 h-5 text-gray-400" />
            )}
            <div>
              <div className="text-sm font-medium text-gray-900 dark:text-gray-100">{year.name}</div>
              {year.is_current && (
                <Badge label={t('admin_pages.academic_years.current')} type="success" size="sm" />
              )}
            </div>
          </div>
        ),
      },

      {
        key: 'start_date',
        labelKey: 'admin_pages.academic_years.start_date',
        render: (year) => (
          <span className="text-sm text-gray-600 dark:text-gray-400">
            {formatDate(year.start_date)}
          </span>
        ),
      },

      {
        key: 'end_date',
        labelKey: 'admin_pages.academic_years.end_date',
        render: (year) => (
          <span className="text-sm text-gray-600 dark:text-gray-400">
            {formatDate(year.end_date)}
          </span>
        ),
      },

      {
        key: 'status',
        labelKey: 'common.status',
        render: (year) => {
          const endDate = new Date(year.end_date);
          const now = new Date();
          const isArchived = !year.is_current && endDate < now;

          return isArchived ? (
            <Badge label={t('admin_pages.academic_years.archived')} type="warning" size="sm" />
          ) : year.is_current ? (
            <Badge label={t('admin_pages.academic_years.current')} type="success" size="sm" />
          ) : (
            <Badge label={t('admin_pages.academic_years.future')} type="info" size="sm" />
          );
        },
      },

      {
        key: 'classes_count',
        labelKey: 'admin_pages.academic_years.classes_count',
        render: (year) => (
          <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
            {year.classes_count || 0}
          </span>
        ),
      },

      {
        key: 'semesters_count',
        labelKey: 'admin_pages.academic_years.semesters_count',
        render: (year) => (
          <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
            {year.semesters_count || 0}
          </span>
        ),
      },
    ],

    actions: [
      {
        labelKey: 'common.view',
        onClick: (year: AcademicYear) => {
          router.visit(route('admin.academic-years.show', year.id));
        },
        color: 'secondary' as const,
        variant: 'outline' as const,
      },
      {
        labelKey: 'admin_pages.academic_years.activate_year',
        onClick: (year: AcademicYear) => {
          onSetCurrent?.(year);
        },
        color: 'primary' as const,
        variant: 'outline' as const,
        permission: 'update academic years',
        conditional: (year: AcademicYear) => !year.is_current && !!onSetCurrent,
      },
      {
        labelKey: 'common.delete',
        onClick: (year: AcademicYear) => {
          onDelete?.(year);
        },
        color: 'danger' as const,
        variant: 'outline' as const,
        permission: 'delete academic years',
        conditional: (year: AcademicYear) => year.classes_count === 0 && year.semesters_count === 0 && !!onDelete,
      },
    ],
  }), [t, onSetCurrent, onDelete]);

  return <BaseEntityList data={data} config={config} variant="admin" showPagination={showPagination} />;
}
