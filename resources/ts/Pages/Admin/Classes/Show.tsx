import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassModel, Assessment, Enrollment, ClassSubject, PageProps, PaginationType } from '@/types';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { Button, Section, Badge, ConfirmationModal, Stat } from '@/Components';
import { EnrollmentList, ClassSubjectList, AssessmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserGroupIcon, BookOpenIcon, CalendarIcon, DocumentTextIcon } from '@heroicons/react/24/outline';

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
  assessments?: PaginationType<Assessment>;
  statistics: ClassStatistics;
  studentsFilters?: {
    search?: string;
  };
  subjectsFilters?: {
    search?: string;
  };
  assessmentsFilters?: {
    search?: string;
    subject_id?: string;
    teacher_id?: string;
    type?: string;
    delivery_mode?: string;
  };
}

export default function ClassShow({
  class: classItem,
  enrollments,
  classSubjects,
  assessments,
  statistics,
  auth,
}: Props) {
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);

  const canUpdate = hasPermission(auth.permissions, 'update classes');
  const canDelete = hasPermission(auth.permissions, 'delete classes');
  const canBeSafelyDeleted = classItem.can_delete ?? false;

  const handleEdit = () => {
    router.visit(route('admin.classes.edit', classItem.id));
  };

  const handleDeleteConfirm = () => {
    router.delete(route('admin.classes.destroy', classItem.id), {
      onSuccess: () => setIsDeleteModalOpen(false),
    });
  };

  const handleBack = () => {
    router.visit(route('admin.classes.index'));
  };

  const enrollmentPercentage = statistics.max_students > 0
    ? (statistics.active_students / statistics.max_students) * 100
    : 0;

  return (
    <AuthenticatedLayout
      title={classItem.name || '-'}
      breadcrumb={breadcrumbs.admin.showClass(classItem)}
    >
      <div className="space-y-6">
        <Section
          title={classItem.name || '-'}
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
                <Button
                  size="sm"
                  variant="outline"
                  color="danger"
                  onClick={() => setIsDeleteModalOpen(true)}
                  disabled={!canBeSafelyDeleted}
                  title={!canBeSafelyDeleted ? trans('admin_pages.classes.cannot_delete_has_data') : undefined}
                >
                  {trans('admin_pages.common.delete')}
                </Button>
              )}
            </div>
          }
        >
          <Stat.Group columns={4}>
            <Stat.Item
              icon={AcademicCapIcon}
              title={trans('admin_pages.classes.level')}
              value={<span className="text-sm text-gray-900">{classItem.level?.name || '-'}</span>}
            />
            <Stat.Item
              icon={CalendarIcon}
              title={trans('admin_pages.classes.academic_year')}
              value={<span className="text-sm text-gray-900">{classItem.academic_year?.name || '-'}</span>}
            />
            <Stat.Item
              icon={UserGroupIcon}
              title={trans('admin_pages.classes.students')}
              value={
                <div className="flex items-center space-x-2">
                  <span className="text-sm font-semibold text-gray-900">
                    {statistics.active_students} / {statistics.max_students}
                  </span>
                  {enrollmentPercentage >= 90 && (
                    <Badge label={trans('admin_pages.classes.full')} type="warning" size="sm" />
                  )}
                </div>
              }
            />
            <Stat.Item
              icon={BookOpenIcon}
              title={trans('admin_pages.classes.subjects')}
              value={<span className="text-sm font-semibold text-gray-900">{statistics.subjects_count}</span>}
            />
            <Stat.Item
              icon={DocumentTextIcon}
              title={trans('admin_pages.classes.assessments')}
              value={<span className="text-sm font-semibold text-gray-900">{statistics.assessments_count}</span>}
            />
          </Stat.Group>
        </Section>

        <Section
          title={trans('admin_pages.classes.enrollments_section')}
          subtitle={trans('admin_pages.classes.enrollments_section_subtitle')}
        >
          <EnrollmentList
            data={enrollments}
            variant="admin"
            showClassColumn={false}
            permissions={{ canView: true }}
            onView={(enrollment) => enrollment.student_id && router.visit(route('admin.users.show', enrollment.student_id))}
          />
        </Section>

        <Section
          title={trans('admin_pages.classes.subjects_section')}
          subtitle={trans('admin_pages.classes.subjects_section_subtitle')}
        >
          <ClassSubjectList
            data={classSubjects}
            variant="admin"
            showClassColumn={false}
          />
        </Section>

        {assessments && (
          <Section
            title={trans('admin_pages.classes.assessments_section')}
            subtitle={trans('admin_pages.classes.assessments_section_subtitle')}
          >
            <AssessmentList
              data={assessments}
              variant="admin"
              showClassColumn={false}
            />
          </Section>
        )}
      </div>

      <ConfirmationModal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        onConfirm={handleDeleteConfirm}
        title={trans('admin_pages.classes.delete_title')}
        message={trans('admin_pages.classes.delete_message', { name: classItem.name })}
        confirmText={trans('admin_pages.common.delete')}
        cancelText={trans('admin_pages.common.cancel')}
        type="danger"
      />
    </AuthenticatedLayout>
  );
}