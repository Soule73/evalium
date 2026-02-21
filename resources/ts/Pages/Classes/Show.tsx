import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import type {
  ClassModel,
  Assessment,
  AssessmentAssignment,
  ClassSubject,
  Enrollment,
  Subject,
  User,
  Semester,
  PageProps,
  PaginationType,
  ClassStatistics,
  ClassRouteContext,
} from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, Badge, ConfirmationModal, Stat } from '@/Components';
import { ClassSubjectList, AssessmentList, EnrollmentList } from '@/Components/shared/lists';
import { CreateClassSubjectModal } from '@/Components/features';
import { route } from 'ziggy-js';
import {
  AcademicCapIcon,
  UserGroupIcon,
  BookOpenIcon,
  CalendarIcon,
  DocumentTextIcon,
  PlusIcon,
} from '@heroicons/react/24/outline';

interface ClassSubjectFormData {
  classes: ClassModel[];
  subjects: Subject[];
  teachers: User[];
  semesters: Semester[];
}

interface EnrollmentWithStudent extends Enrollment {
  student?: User;
}

interface Props extends PageProps {
  class: ClassModel;
  routeContext: ClassRouteContext;
  classSubjects: PaginationType<ClassSubject>;
  assessments: PaginationType<Assessment>;
  students?: PaginationType<EnrollmentWithStudent>;
  statistics?: ClassStatistics;
  classSubjectFormData?: ClassSubjectFormData;
}

export default function ClassShow({
  class: classItem,
  routeContext,
  classSubjects,
  assessments,
  students,
  statistics,
  classSubjectFormData,
  auth,
}: Props) {
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [isAssignSubjectModalOpen, setIsAssignSubjectModalOpen] = useState(false);
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const isAdmin = routeContext.role === 'admin';
  const canUpdate = isAdmin && hasPermission(auth.permissions, 'update classes');
  const canDelete = isAdmin && hasPermission(auth.permissions, 'delete classes');
  const canCreateSubject = isAdmin && hasPermission(auth.permissions, 'create class subjects');
  const canBeSafelyDeleted = classItem.can_delete ?? false;

  const enrollmentPercentage =
    statistics && statistics.max_students > 0
      ? (statistics.active_students / statistics.max_students) * 100
      : 0;

  const translations = useMemo(
    () => ({
      showSubtitle: isAdmin
        ? t('admin_pages.classes.show_subtitle')
        : t('teacher_class_pages.show.show_subtitle'),
      back: isAdmin ? t('commons/ui.back') : t('teacher_class_pages.show.back'),
      edit: t('commons/ui.edit'),
      cannotDeleteHasData: t('admin_pages.classes.cannot_delete_has_data'),
      delete: t('commons/ui.delete'),
      level: t('admin_pages.classes.level'),
      academicYear: t('admin_pages.classes.academic_year'),
      students: t('admin_pages.classes.students'),
      full: t('admin_pages.classes.full'),
      subjects: t('admin_pages.classes.subjects'),
      mySubjects: t('teacher_class_pages.show.my_subjects'),
      assessmentsLabel: t('admin_pages.classes.assessments'),
      allAssessments: t('teacher_class_pages.show.all_assessments'),
      studentsSection: t('admin_pages.classes.students_section'),
      studentsSectionSubtitle: t('admin_pages.classes.students_section_subtitle'),
      seeAllStudents: t('admin_pages.classes.see_all_students'),
      subjectsSection: isAdmin
        ? t('admin_pages.classes.subjects_section')
        : t('teacher_class_pages.show.subjects_section_title'),
      subjectsSectionSubtitle: isAdmin
        ? t('admin_pages.classes.subjects_section_subtitle')
        : t('teacher_class_pages.show.subjects_section_subtitle'),
      assignSubject: t('admin_pages.classes.assign_subject'),
      seeAllSubjects: t('admin_pages.classes.see_all_subjects'),
      assessmentsSection: isAdmin
        ? t('admin_pages.classes.assessments_section')
        : t('teacher_class_pages.show.recent_assessments_title'),
      assessmentsSectionSubtitle: isAdmin
        ? t('admin_pages.classes.assessments_section_subtitle')
        : t('teacher_class_pages.show.recent_assessments_subtitle'),
      seeAllAssessments: isAdmin
        ? t('admin_pages.classes.see_all_assessments')
        : t('teacher_class_pages.show.view_all'),
      studentsSectionTeacher: t('teacher_class_pages.show.students_section_title'),
      deleteTitle: t('admin_pages.classes.delete_title'),
      cancel: t('commons/ui.cancel'),
    }),
    [t, isAdmin],
  );

  const deleteMessage = useMemo(
    () =>
      t('admin_pages.classes.delete_message', {
        name: classItem.display_name ?? classItem.name,
      }),
    [t, classItem.display_name, classItem.name],
  );

  const breadcrumb = isAdmin
    ? breadcrumbs.admin.showClass(classItem)
    : breadcrumbs.teacher.showClass(classItem);

  const handleBack = () => {
    router.visit(route(routeContext.indexRoute));
  };

  const handleEdit = () => {
    if (routeContext.editRoute) {
      router.visit(route(routeContext.editRoute, classItem.id));
    }
  };

  const handleDeleteConfirm = () => {
    if (routeContext.deleteRoute) {
      router.delete(route(routeContext.deleteRoute, classItem.id), {
        onSuccess: () => setIsDeleteModalOpen(false),
      });
    }
  };

  const handleViewAssessments = () => {
    if (routeContext.assessmentsRoute) {
      router.visit(route(routeContext.assessmentsRoute, classItem.id));
    } else {
      router.visit(route('teacher.assessments.index', { class_id: classItem.id }));
    }
  };

  const handleCreateAssessment = (classSubject: ClassSubject) => {
    router.visit(route('teacher.assessments.create', { class_subject_id: classSubject.id }));
  };

  const handleViewAssessment = (item: Assessment | AssessmentAssignment) => {
    const assessment = item as Assessment;
    if (routeContext.assessmentShowRoute) {
      router.visit(
        route(routeContext.assessmentShowRoute, {
          class: assessment.class_subject?.class_id ?? classItem.id,
          assessment: assessment.id,
        }),
      );
    } else {
      router.visit(route('teacher.assessments.show', assessment.id));
    }
  };

  const adminActions = (
    <div className="flex space-x-3">
      <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
        {translations.back}
      </Button>
      {canUpdate && (
        <Button size="sm" variant="solid" color="primary" onClick={handleEdit}>
          {translations.edit}
        </Button>
      )}
      {canDelete && (
        <Button
          size="sm"
          variant="outline"
          color="danger"
          onClick={() => setIsDeleteModalOpen(true)}
          disabled={!canBeSafelyDeleted}
          title={!canBeSafelyDeleted ? translations.cannotDeleteHasData : undefined}
        >
          {translations.delete}
        </Button>
      )}
    </div>
  );

  const teacherActions = (
    <div className="flex space-x-3">
      <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
        {translations.back}
      </Button>
      <Button size="sm" variant="solid" color="primary" onClick={handleViewAssessments}>
        {translations.allAssessments}
      </Button>
    </div>
  );

  const adminStats = statistics && (
    <Stat.Group columns={4}>
      <Stat.Item
        icon={AcademicCapIcon}
        title={translations.level}
        value={
          <span className="text-sm text-gray-900">{classItem.level?.name || '-'}</span>
        }
      />
      <Stat.Item
        icon={CalendarIcon}
        title={translations.academicYear}
        value={
          <span className="text-sm text-gray-900">
            {classItem.academic_year?.name || '-'}
          </span>
        }
      />
      <Stat.Item
        icon={UserGroupIcon}
        title={translations.students}
        value={
          <div className="flex items-center space-x-2">
            <span className="text-sm font-semibold text-gray-900">
              {statistics.active_students} / {statistics.max_students}
            </span>
            {enrollmentPercentage >= 90 && (
              <Badge label={translations.full} type="warning" size="sm" />
            )}
          </div>
        }
      />
      <Stat.Item
        icon={BookOpenIcon}
        title={translations.subjects}
        value={
          <span className="text-sm font-semibold text-gray-900">
            {statistics.subjects_count}
          </span>
        }
      />
      <Stat.Item
        icon={DocumentTextIcon}
        title={translations.assessmentsLabel}
        value={
          <span className="text-sm font-semibold text-gray-900">
            {statistics.assessments_count}
          </span>
        }
      />
    </Stat.Group>
  );

  const teacherStats = (
    <Stat.Group columns={4}>
      <Stat.Item
        icon={AcademicCapIcon}
        title={translations.level}
        value={
          <span className="text-sm text-gray-900">{classItem.level?.name || '-'}</span>
        }
      />
      <Stat.Item
        icon={CalendarIcon}
        title={translations.academicYear}
        value={
          <span className="text-sm text-gray-900">
            {classItem.academic_year?.name || '-'}
          </span>
        }
      />
      <Stat.Item
        icon={UserGroupIcon}
        title={translations.students}
        value={
          <span className="text-sm font-semibold text-gray-900">
            {students?.total ?? 0}
          </span>
        }
      />
      <Stat.Item
        icon={BookOpenIcon}
        title={translations.mySubjects}
        value={
          <span className="text-sm font-semibold text-gray-900">
            {classSubjects.total}
          </span>
        }
      />
    </Stat.Group>
  );

  return (
    <AuthenticatedLayout
      title={classItem.display_name ?? classItem.name ?? '-'}
      breadcrumb={breadcrumb}
    >
      <div className="space-y-6">
        <Section
          title={classItem.display_name ?? classItem.name ?? '-'}
          subtitle={translations.showSubtitle}
          actions={isAdmin ? adminActions : teacherActions}
        >
          {isAdmin ? adminStats : teacherStats}
        </Section>

        {isAdmin && statistics && (
          <Section
            title={translations.studentsSection}
            subtitle={translations.studentsSectionSubtitle}
            actions={
              <Button
                size="sm"
                variant="outline"
                color="secondary"
                onClick={() =>
                  router.visit(
                    route('admin.classes.students.index', classItem.id),
                  )
                }
              >
                {translations.seeAllStudents}
              </Button>
            }
          >
            <div className="flex items-center gap-2 text-sm text-gray-600">
              <UserGroupIcon className="h-5 w-5" />
              <span>
                {statistics.active_students} / {statistics.max_students}{' '}
                {translations.students}
              </span>
              {enrollmentPercentage >= 90 && (
                <Badge label={translations.full} type="warning" size="sm" />
              )}
            </div>
          </Section>
        )}

        <Section
          title={translations.subjectsSection}
          subtitle={translations.subjectsSectionSubtitle}
          actions={
            isAdmin ? (
              <div className="flex items-center gap-3">
                <Button
                  size="sm"
                  variant="outline"
                  color="secondary"
                  onClick={() =>
                    router.visit(
                      route('admin.classes.subjects', classItem.id),
                    )
                  }
                >
                  {translations.seeAllSubjects}
                </Button>
                {canCreateSubject && (
                  <Button
                    size="sm"
                    variant="solid"
                    color="primary"
                    onClick={() => setIsAssignSubjectModalOpen(true)}
                  >
                    <PlusIcon className="w-4 h-4 mr-1" />
                    {translations.assignSubject}
                  </Button>
                )}
              </div>
            ) : undefined
          }
        >
          <ClassSubjectList
            data={classSubjects}
            variant={routeContext.role}
            showClassColumn={false}
            showPagination={!isAdmin}
            {...(!isAdmin && {
              showTeacherColumn: false,
              onCreateAssessment: handleCreateAssessment,
            })}
          />
        </Section>

        <Section
          title={translations.assessmentsSection}
          subtitle={translations.assessmentsSectionSubtitle}
          actions={
            <Button
              size="sm"
              variant="outline"
              color="secondary"
              onClick={handleViewAssessments}
            >
              {translations.seeAllAssessments}
            </Button>
          }
        >
          <AssessmentList
            data={assessments}
            variant={routeContext.role}
            showClassColumn={false}
            showPagination={!isAdmin}
            onView={handleViewAssessment}
          />
        </Section>

        {!isAdmin && students && (
          <Section
            title={translations.studentsSectionTeacher}
            subtitle={t('teacher_class_pages.show.students_section_subtitle', {
              count: students.total,
            })}
          >
            <EnrollmentList
              data={students as PaginationType<Enrollment>}
              variant="teacher"
              showClassColumn={false}
            />
          </Section>
        )}
      </div>

      {isAdmin && (
        <>
          <ConfirmationModal
            isOpen={isDeleteModalOpen}
            onClose={() => setIsDeleteModalOpen(false)}
            onConfirm={handleDeleteConfirm}
            title={translations.deleteTitle}
            message={deleteMessage}
            confirmText={translations.delete}
            cancelText={translations.cancel}
            type="danger"
          />
          {classSubjectFormData && (
            <CreateClassSubjectModal
              isOpen={isAssignSubjectModalOpen}
              onClose={() => setIsAssignSubjectModalOpen(false)}
              formData={classSubjectFormData}
              classId={classItem.id}
              redirectTo={route('admin.classes.show', classItem.id)}
            />
          )}
        </>
      )}
    </AuthenticatedLayout>
  );
}
