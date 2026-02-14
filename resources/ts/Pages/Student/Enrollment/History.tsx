import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Enrollment, type PageProps } from '@/types';
import { Button, EmptyState, Section } from '@/Components';
import { EnrollmentList } from '@/Components/shared/lists';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { ClockIcon } from '@heroicons/react/24/outline';

interface StudentEnrollmentHistoryProps extends PageProps {
  enrollments: Enrollment[];
}

export default function History({ enrollments }: StudentEnrollmentHistoryProps) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const translations = useMemo(
    () => ({
      title: t('student_enrollment_pages.history.title'),
      subtitle: t('student_enrollment_pages.history.subtitle'),
      backToCurrent: t('student_enrollment_pages.history.back_to_current'),
      emptyTitle: t('student_enrollment_pages.history.empty_title'),
      emptySubtitle: t('student_enrollment_pages.history.empty_subtitle'),
    }),
    [t]
  );

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.student.enrollmentHistory()}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.visit(route('student.enrollment.show'))}
          >
            {translations.backToCurrent}
          </Button>
        }
      >
        {enrollments.length === 0 ? (
          <EmptyState
            icon={<ClockIcon className="w-12 h-12" />}
            title={translations.emptyTitle}
            subtitle={translations.emptySubtitle}
          />
        ) : (
          <EnrollmentList
            data={{
              data: enrollments,
              current_page: 1,
              last_page: 1,
              per_page: enrollments.length,
              total: enrollments.length,
              first_page_url: '',
              from: 1,
              last_page_url: '',
              next_page_url: null,
              path: '',
              prev_page_url: null,
              to: enrollments.length,
              links: [],
            }}
            variant="student"
          />
        )}
      </Section>
    </AuthenticatedLayout>
  );
}
