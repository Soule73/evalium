import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Enrollment, type User, type PageProps } from '@/types';
import type { PaginationType } from '@/types/datatable';
import { Button, Section } from '@/Components';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { breadcrumbs } from '@/utils/helpers/breadcrumbs';
import { UserList } from '@/Components/shared/lists';

interface StudentEnrollmentClassmatesProps extends PageProps {
  enrollment: Enrollment;
  classmates: User[];
}

export default function Classmates({ enrollment, classmates }: StudentEnrollmentClassmatesProps) {
  const { t } = useTranslations();

  const translations = useMemo(
    () => ({
      title: t('student_enrollment_pages.classmates.title'),
      backToEnrollment: t('student_enrollment_pages.classmates.back_to_enrollment'),
    }),
    [t]
  );

  const classmatesCountTranslation = useMemo(() => t('student_enrollment_pages.classmates.classmates_count', { count: classmates.length }), [t, classmates.length]);

  const subtitleTranslation = useMemo(() => t('student_enrollment_pages.classmates.subtitle', { class: enrollment.class?.name || '-' }), [t, enrollment.class?.name]);

  const paginatedClassmates: PaginationType<User> = {
    data: classmates,
    current_page: 1,
    last_page: 1,
    per_page: classmates.length,
    total: classmates.length,
    first_page_url: '',
    from: 1,
    last_page_url: '',
    next_page_url: null,
    path: '',
    prev_page_url: null,
    to: classmates.length,
    links: []
  };

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.student.enrollmentClassmates()}>
      <Section
        title={classmatesCountTranslation}
        subtitle={subtitleTranslation}
        actions={
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.visit(route('student.enrollment.show'))}
          >
            {translations.backToEnrollment}
          </Button>
        }
      >
        <UserList data={paginatedClassmates} variant="classmates" />
      </Section>
    </AuthenticatedLayout>
  );
}
