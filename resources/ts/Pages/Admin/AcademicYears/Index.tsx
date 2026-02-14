import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type DataTableConfig, type PaginationType } from '@/types/datatable';
import { CalendarIcon } from '@heroicons/react/24/outline';
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

export default function AcademicYearIndex({ academicYears, auth }: Props) {
  const [archiveModal, setArchiveModal] = useState<{ isOpen: boolean; year: AcademicYear | null }>({
    isOpen: false,
    year: null,
  });
  const [setCurrentModal, setSetCurrentModal] = useState<{ isOpen: boolean; year: AcademicYear | null }>({
    isOpen: false,
    year: null,
  });

  const canCreate = hasPermission(auth.permissions, 'create academic years');
  const canUpdate = hasPermission(auth.permissions, 'update academic years');
  const canDelete = hasPermission(auth.permissions, 'delete academic years');

  const handleCreate = () => {
    router.visit(route('admin.academic-years.create'));
  };

  const handleView = (id: number) => {
    router.visit(route('admin.academic-years.show', id));
  };

  const handleEdit = (id: number) => {
    router.visit(route('admin.academic-years.edit', id));
  };

  const handleSetCurrent = (year: AcademicYear) => {
    setSetCurrentModal({ isOpen: true, year });
  };

  const confirmSetCurrent = () => {
    if (setCurrentModal.year) {
      router.post(
        route('admin.academic-years.set-current', setCurrentModal.year.id),
        {},
        {
          onSuccess: () => {
            setSetCurrentModal({ isOpen: false, year: null });
          },
        }
      );
    }
  };

  const handleArchive = (year: AcademicYear) => {
    setArchiveModal({ isOpen: true, year });
  };

  const confirmArchive = () => {
    if (archiveModal.year) {
      router.post(
        route('admin.academic-years.archive', archiveModal.year.id),
        {},
        {
          onSuccess: () => {
            setArchiveModal({ isOpen: false, year: null });
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
          <div className="flex items-center space-x-2">
            <CalendarIcon className="w-5 h-5 text-gray-400" />
            <div>
              <div className="text-sm font-medium text-gray-900">{year.name}</div>
              {year.is_current && (
                <Badge label={trans('admin_pages.academic_years.current')} type="success" size="sm" />
              )}
            </div>
          </div>
        ),
      },
      {
        key: 'period',
        label: trans('admin_pages.academic_years.period'),
        render: (year) => (
          <div className="text-sm text-gray-600">
            <div>{formatDate(year.start_date)}</div>
            <div className="text-xs text-gray-400">{formatDate(year.end_date)}</div>
          </div>
        ),
      },
      {
        key: 'semesters',
        label: trans('admin_pages.academic_years.semesters'),
        render: (year) => (
          <div className="text-sm text-gray-600">
            {year.semesters_count || 0} {trans('admin_pages.academic_years.semesters_count')}
          </div>
        ),
      },
      {
        key: 'classes',
        label: trans('admin_pages.academic_years.classes'),
        render: (year) => (
          <div className="text-sm text-gray-600">
            {year.classes_count || 0} {trans('admin_pages.academic_years.classes_count')}
          </div>
        ),
      },
      {
        key: 'status',
        label: trans('admin_pages.academic_years.status'),
        render: (year) => (
          <Badge
            label={year.is_current ? trans('admin_pages.common.active') : trans('admin_pages.common.inactive')}
            type={year.is_current ? 'success' : 'info'}
          />
        ),
      },
      {
        key: 'actions',
        label: trans('admin_pages.common.actions'),
        render: (year) => (
          <div className="flex space-x-2">
            <Button onClick={() => handleView(year.id)} color="secondary" size="sm" variant="outline">
              {trans('admin_pages.common.view')}
            </Button>
            {canUpdate && (
              <>
                <Button onClick={() => handleEdit(year.id)} color="primary" size="sm" variant="outline">
                  {trans('admin_pages.common.edit')}
                </Button>
                {!year.is_current && (
                  <Button
                    onClick={() => handleSetCurrent(year)}
                    color="success"
                    size="sm"
                    variant="outline"
                  >
                    {trans('admin_pages.academic_years.set_current')}
                  </Button>
                )}
                {year.is_current && (
                  <Button
                    onClick={() => handleArchive(year)}
                    color="warning"
                    size="sm"
                    variant="outline"
                  >
                    {trans('admin_pages.academic_years.archive')}
                  </Button>
                )}
              </>
            )}
            {canDelete && (
              <Button onClick={() => handleDelete(year.id)} color="danger" size="sm" variant="outline">
                {trans('admin_pages.common.delete')}
              </Button>
            )}
          </div>
        ),
      },
    ],
    searchPlaceholder: trans('admin_pages.academic_years.search_placeholder'),
    filters: [
      {
        key: 'is_current',
        type: 'select',
        label: trans('admin_pages.academic_years.filter_status'),
        options: [
          { label: trans('admin_pages.academic_years.all_statuses'), value: '' },
          { label: trans('admin_pages.academic_years.current'), value: '1' },
          { label: trans('admin_pages.academic_years.archived'), value: '0' },
        ],
      },
    ],
  };

  return (
    <AuthenticatedLayout title={trans('admin_pages.academic_years.page_title')} breadcrumb={breadcrumbs.admin.academicYears()}>
      <Section
        title={trans('admin_pages.academic_years.title')}
        subtitle={trans('admin_pages.academic_years.subtitle')}
        actions={
          canCreate && (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {trans('admin_pages.academic_years.create')}
            </Button>
          )
        }
      >
        <DataTable data={academicYears} config={dataTableConfig} />
      </Section>

      <ConfirmationModal
        isOpen={setCurrentModal.isOpen}
        onClose={() => setSetCurrentModal({ isOpen: false, year: null })}
        onConfirm={confirmSetCurrent}
        title={trans('admin_pages.academic_years.set_current_title')}
        message={trans('admin_pages.academic_years.set_current_message', { name: setCurrentModal.year?.name || '' })}
        confirmText={trans('admin_pages.common.confirm')}
        cancelText={trans('admin_pages.common.cancel')}
        type="info"
      />

      <ConfirmationModal
        isOpen={archiveModal.isOpen}
        onClose={() => setArchiveModal({ isOpen: false, year: null })}
        onConfirm={confirmArchive}
        title={trans('admin_pages.academic_years.archive_title')}
        message={trans('admin_pages.academic_years.archive_message', { name: archiveModal.year?.name || '' })}
        confirmText={trans('admin_pages.common.confirm')}
        cancelText={trans('admin_pages.common.cancel')}
        type="warning"
      />
    </AuthenticatedLayout>
  );
}
