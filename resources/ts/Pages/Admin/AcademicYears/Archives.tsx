import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type DataTableConfig, type PaginationType } from '@/types/datatable';
import { CheckCircleIcon, ArchiveBoxIcon } from '@heroicons/react/24/outline';
import { type AcademicYear, type PageProps } from '@/types';
import { breadcrumbs, trans, formatDate, hasPermission } from '@/utils';
import { Badge, Button, ConfirmationModal, DataTable, Section } from '@/Components';
import { route } from 'ziggy-js';

interface Props extends PageProps {
  academicYears: PaginationType<AcademicYear>;
  filters: Filters;
}

interface Filters {
  search?: string;
  is_current?: string;
}

export default function AcademicYearArchives({ academicYears, auth }: Props) {
  const [setCurrentModal, setSetCurrentModal] = useState<{ isOpen: boolean; year: AcademicYear | null }>({
    isOpen: false,
    year: null,
  });

  const canUpdate = hasPermission(auth.permissions, 'update academic years');
  const canDelete = hasPermission(auth.permissions, 'delete academic years');

  const handleView = (year: AcademicYear) => {
    router.visit(route('admin.academic-years.show', year.id));
  };

  const handleSetCurrentAndNavigate = (year: AcademicYear) => {
    setSetCurrentModal({ isOpen: true, year });
  };

  const confirmSetCurrent = () => {
    if (setCurrentModal.year) {
      router.post(
        '/academic-years/set-current',
        { academic_year_id: setCurrentModal.year.id },
        {
          onSuccess: () => {
            setSetCurrentModal({ isOpen: false, year: null });
            router.visit(route('dashboard'));
          },
        }
      );
    }
  };

  const handleDelete = (id: number) => {
    if (confirm(trans('admin_pages.academic_years.confirm_delete'))) {
      router.delete(route('admin.academic-years.destroy', id));
    }
  };

  const dataTableConfig: DataTableConfig<AcademicYear> = {
    columns: [
      {
        key: 'name',
        label: trans('admin_pages.academic_years.name'),
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
                <Badge label={trans('admin_pages.academic_years.current')} type="success" size="sm" />
              )}
            </div>
          </div>
        ),
        sortable: true,
      },
      {
        key: 'start_date',
        label: trans('admin_pages.academic_years.start_date'),
        render: (year) => (
          <span className="text-sm text-gray-600 dark:text-gray-400">
            {formatDate(year.start_date)}
          </span>
        ),
        sortable: true,
      },
      {
        key: 'end_date',
        label: trans('admin_pages.academic_years.end_date'),
        render: (year) => (
          <span className="text-sm text-gray-600 dark:text-gray-400">
            {formatDate(year.end_date)}
          </span>
        ),
        sortable: true,
      },
      {
        key: 'status',
        label: trans('common.status'),
        render: (year) => {
          const endDate = new Date(year.end_date);
          const now = new Date();
          const isArchived = !year.is_current && endDate < now;

          return isArchived ? (
            <Badge label={trans('admin_pages.academic_years.archived')} type="warning" size="sm" />
          ) : year.is_current ? (
            <Badge label={trans('admin_pages.academic_years.current')} type="success" size="sm" />
          ) : (
            <Badge label={trans('admin_pages.academic_years.future')} type="info" size="sm" />
          );
        },
      },
      {
        key: 'classes_count',
        label: trans('admin_pages.academic_years.classes_count'),
        render: (year) => (
          <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
            {year.classes_count || 0}
          </span>
        ),
        sortable: true,
      },
      {
        key: 'semesters_count',
        label: trans('admin_pages.academic_years.semesters_count'),
        render: (year) => (
          <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
            {year.semesters_count || 0}
          </span>
        ),
        sortable: true,
      },
      {
        key: 'actions',
        label: trans('common.actions'),
        render: (year) => (
          <div className="flex space-x-2">
            <Button onClick={() => handleView(year)} color="secondary" size="sm" variant="outline">
              {trans('common.view')}
            </Button>
            {!year.is_current && canUpdate && (
              <Button
                onClick={() => handleSetCurrentAndNavigate(year)}
                color="primary"
                size="sm"
                variant="outline"
              >
                {trans('admin_pages.academic_years.activate_year')}
              </Button>
            )}
            {canDelete && (year.classes_count === 0 && year.semesters_count === 0) && (
              <Button onClick={() => handleDelete(year.id)} color="danger" size="sm" variant="outline">
                {trans('common.delete')}
              </Button>
            )}
          </div>
        ),
      },
    ],
    emptyState: {
      title: trans('admin_pages.academic_years.no_years_found'),
      subtitle: trans('admin_pages.academic_years.no_years_description'),
    },
  };

  return (
    <AuthenticatedLayout breadcrumb={breadcrumbs.adminAcademicYears()}>
      <Section
        title={trans('admin_pages.academic_years.archives_title')}
        subtitle={trans('admin_pages.academic_years.archives_subtitle')}
      >
        <DataTable
          config={dataTableConfig}
          data={academicYears}
        />
      </Section>

      <ConfirmationModal
        isOpen={setCurrentModal.isOpen}
        title={trans('admin_pages.academic_years.activate_year_modal_title')}
        message={trans('admin_pages.academic_years.activate_year_modal_message', { name: setCurrentModal.year?.name || '' })}
        confirmText={trans('admin_pages.academic_years.activate_and_switch')}
        cancelText={trans('common.cancel')}
        onConfirm={confirmSetCurrent}
        onClose={() => setSetCurrentModal({ isOpen: false, year: null })}
      />
    </AuthenticatedLayout>
  );
}
