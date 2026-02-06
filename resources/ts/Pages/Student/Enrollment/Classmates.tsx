import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, User, PageProps } from '@/types';
import type { PaginationType } from '@/types/datatable';
import { Button, Section } from '@/Components';
import { trans } from '@/utils';
import { UserList } from '@/Components/shared/lists';

interface StudentEnrollmentClassmatesProps extends PageProps {
  enrollment: Enrollment;
  classmates: User[];
}

export default function Classmates({ enrollment, classmates }: StudentEnrollmentClassmatesProps) {
  const translations = useMemo(
    () => ({
      title: trans('student_enrollment_pages.classmates.title'),
      subtitle: trans('student_enrollment_pages.classmates.subtitle', {
        class: enrollment.class?.name || '-',
      }),
      backToEnrollment: trans('student_enrollment_pages.classmates.back_to_enrollment'),
      classmatesCount: trans('student_enrollment_pages.classmates.classmates_count', {
        count: classmates.length,
      }),
    }),
    [enrollment.class?.name, classmates.length]
  );

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
    <AuthenticatedLayout title={translations.title}>
      <Section
        title={translations.classmatesCount}
        subtitle={translations.subtitle}
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
