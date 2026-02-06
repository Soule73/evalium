import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassModel, Enrollment, ClassSubject, PageProps, PaginationType } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section, Badge, DataTable } from '@/Components';
import { EnrollmentList } from '@/Components/shared/lists';
import { DataTableConfig } from '@/types/datatable';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserGroupIcon, BookOpenIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface ClassStatistics {
  total_students: number;
  active_students: number;
  withdrawn_students: number;
  subjects_count: number;
  assessments_count: number;
  max_students: number;
  available_slots: number;
}

interface Props extends PageProps {
  class: ClassModel;
  enrollments: PaginationType<Enrollment>;
  classSubjects: PaginationType<ClassSubject>;
  statistics: ClassStatistics;
  studentsFilters?: {
    search?: string;
  };
  subjectsFilters?: {
    search?: string;
  };
}

export default function ClassShow({
  class: classItem,
  enrollments,
  classSubjects,
  statistics,
  studentsFilters,
  auth,
}: Props) {
  const canUpdate = hasPermission(auth.permissions, 'update classes');
  const canDelete = hasPermission(auth.permissions, 'delete classes');

  const handleEdit = () => {
    router.visit(route('admin.classes.edit', classItem.id));
  };

  const handleDelete = () => {
    if (confirm(trans('adminss_pages.classes.delete_confirm'))) {
      router.delete(route('admin.classes.destroy', classItem.id));
    }
  };

  const handleBack = () => {
    router.visit(route('admin.classes.index'));
  };

  const enrollmentPercentage = statistics.max_students > 0
    ? (statistics.active_students / statistics.max_students) * 100
    : 0;

  const subjectsTableConfig: DataTableConfig<ClassSubject> = {
    columns: [
      {
        key: 'subject',
        label: trans('admin_pages.classes.subject'),
        render: (classSubject) => (
          <div>
            <div className="font-medium text-gray-900">{classSubject.subject?.name}</div>
            <div className="text-sm text-gray-500">{classSubject.subject?.code}</div>
          </div>
        ),
      },
      {
        key: 'teacher',
        label: trans('admin_pages.classes.teacher'),
        render: (classSubject) => (
          <div className="text-sm text-gray-900">
            {classSubject.teacher?.name || '-'}
          </div>
        ),
      },
      {
        key: 'semester',
        label: trans('admin_pages.classes.semester'),
        render: (classSubject) => (
          <div className="text-sm text-gray-600">
            {classSubject.semester?.name || '-'}
          </div>
        ),
      },
      {
        key: 'assessments',
        label: trans('admin_pages.classes.assessments'),
        render: (classSubject) => (
          <div className="text-sm text-gray-600">
            {classSubject.assessments_count || 0}
          </div>
        ),
      },
    ],
    filters: [],
    emptyState: {
      title: trans('admin_pages.classes.no_subjects'),
      subtitle: trans('admin_pages.classes.no_subjects_subtitle'),
    },
    searchable: true,
    searchPlaceholder: trans('admin_pages.classes.search_subjects'),
    onSearch: (search) => {
      router.get(
        route('admin.classes.show', classItem.id),
        { subjects_search: search, students_search: studentsFilters?.search },
        { preserveState: true, preserveScroll: true }
      );
    },
    pageName: 'subjects_page',
    perPageName: 'subjects_per_page',
  };

  return (
    <AuthenticatedLayout
      title={classItem.display_name || classItem.name}
      breadcrumb={breadcrumbs.admin.showClass(classItem)}
    >
      <div className="space-y-6">
        <Section
          title={classItem.display_name || classItem.name}
          subtitle={trans('admin_pages.classes.show_subtitle')}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {trans('admin_pages.common.back')}
              </Button>
              {canUpdate && (
                <Button size="sm" variant="solid" color="primary" onClick={handleEdit}>
                  {trans('admin_pages.common.edit')}
                </Button>
              )}
              {canDelete && (
                <Button size="sm" variant="outline" color="danger" onClick={handleDelete}>
                  {trans('admin_pages.common.delete')}
                </Button>
              )}
            </div>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.level')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {classItem.level?.name || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <CalendarIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.academic_year')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {classItem.academic_year?.name || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <UserGroupIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.students')}
                </div>
                <div className="mt-1 flex items-center space-x-2">
                  <span className="text-sm font-semibold text-gray-900">
                    {statistics.active_students} / {statistics.max_students}
                  </span>
                  {enrollmentPercentage >= 90 && (
                    <Badge label={trans('admin_pages.classes.full')} type="warning" size="sm" />
                  )}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <BookOpenIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('admin_pages.classes.subjects')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {statistics.subjects_count}
                </div>
              </div>
            </div>
          </div>
        </Section>

        <Section
          title={trans('admin_pages.classes.enrollments_section')}
          subtitle={trans('admin_pages.classes.enrollments_section_subtitle')}
        >
          <EnrollmentList data={enrollments} variant="admin" showActions={false} />
        </Section>

        <Section
          title={trans('admin_pages.classes.subjects_section')}
          subtitle={trans('admin_pages.classes.subjects_section_subtitle')}
        >
          <DataTable data={classSubjects} config={subjectsTableConfig} />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}