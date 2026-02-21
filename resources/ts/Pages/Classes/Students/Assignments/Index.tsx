import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import type {
  ClassModel,
  Enrollment,
  AssessmentAssignment,
  PageProps,
  PaginationType,
  ClassRouteContext,
} from '@/types';

interface ClassSubjectOption {
  id: number;
  subject_name: string;
  teacher_name: string;
}

interface Props extends PageProps {
  class: ClassModel;
  enrollment: Enrollment;
  assignments: PaginationType<AssessmentAssignment>;
  subjects: ClassSubjectOption[];
  filters: {
    search?: string;
    class_subject_id?: string;
    status?: string;
  };
  routeContext: ClassRouteContext;
}

/**
 * Shared page listing all assessment assignments for a student within a class.
 * Used by both admin and teacher contexts via routeContext.
 */
export default function ClassStudentAssignmentsIndex({
  class: classItem,
  enrollment,
  assignments,
  subjects,
  routeContext,
}: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const isAdmin = routeContext.role === 'admin';

  const translations = useMemo(
    () => ({
      title: t('admin_pages.enrollments.assignments_title'),
      subtitle: t('admin_pages.enrollments.assignments_subtitle', {
        student: enrollment.student?.name || '',
        class: enrollment.class?.display_name ?? enrollment.class?.name ?? '',
      }),
      back: t('admin_pages.enrollments.back_to_enrollment'),
    }),
    [t, enrollment.student?.name, enrollment.class?.name, enrollment.class?.display_name],
  );

  const pageBreadcrumbs = useMemo(
    () =>
      isAdmin
        ? breadcrumbs.admin.classStudentAssignments(classItem, enrollment)
        : breadcrumbs.teacher.classStudentAssignments(classItem, enrollment),
    [breadcrumbs, classItem, enrollment, isAdmin],
  );

  const handleBack = () => {
    if (routeContext.studentShowRoute) {
      router.visit(
        route(routeContext.studentShowRoute, {
          class: classItem.id,
          enrollment: enrollment.id,
        }),
      );
    }
  };

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={pageBreadcrumbs}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          <Button
            size="sm"
            variant="outline"
            color="secondary"
            onClick={handleBack}
          >
            {translations.back}
          </Button>
        }
      >
        <AssessmentList
          data={assignments}
          variant="class-assignment"
          enrollment={enrollment}
          subjects={subjects}
        />
      </Section>
    </AuthenticatedLayout>
  );
}
