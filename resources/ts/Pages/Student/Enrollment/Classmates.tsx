import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, User, PageProps } from '@/types';
import { Button, Section } from '@/Components';
import { trans } from '@/utils';
import { ClassmatesList } from '@/Components/shared/lists';

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
        <ClassmatesList classmates={classmates} />
      </Section>
    </AuthenticatedLayout>
  );
}
