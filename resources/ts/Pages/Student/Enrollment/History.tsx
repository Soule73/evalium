import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, PageProps } from '@/types';
import { Badge, Button, DataTable, EmptyState, Section } from '@/Components';
import { trans, formatDate } from '@/utils';
import { ClockIcon } from '@heroicons/react/24/outline';

interface StudentEnrollmentHistoryProps extends PageProps {
  enrollments: Enrollment[];
}

export default function History({ enrollments }: StudentEnrollmentHistoryProps) {
  const translations = useMemo(
    () => ({
      title: trans('student_enrollment_pages.history.title'),
      subtitle: trans('student_enrollment_pages.history.subtitle'),
      backToCurrent: trans('student_enrollment_pages.history.back_to_current'),
      academicYear: trans('student_enrollment_pages.history.academic_year'),
      class: trans('student_enrollment_pages.history.class'),
      level: trans('student_enrollment_pages.history.level'),
      enrolledOn: trans('student_enrollment_pages.history.enrolled_on'),
      completedOn: trans('student_enrollment_pages.history.completed_on'),
      status: trans('student_enrollment_pages.history.status'),
      active: trans('student_enrollment_pages.history.active'),
      completed: trans('student_enrollment_pages.history.completed'),
      inProgress: trans('student_enrollment_pages.history.in_progress'),
      emptyTitle: trans('student_enrollment_pages.history.empty_title'),
      emptySubtitle: trans('student_enrollment_pages.history.empty_subtitle'),
      notAvailable: trans('student_enrollment_pages.history.not_available'),
    }),
    []
  );

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'active':
        return <Badge label={translations.active} type="success" />;
      case 'completed':
        return <Badge label={translations.completed} type="info" />;
      default:
        return <Badge label={status} type="gray" />;
    }
  };

  const columns = [
    {
      key: 'academic_year',
      label: translations.academicYear,
      render: (enrollment: Enrollment) => (
        <span className="font-medium text-gray-900">{enrollment.class?.academic_year?.name || '-'}</span>
      ),
    },
    {
      key: 'class',
      label: translations.class,
      render: (enrollment: Enrollment) => <span className="text-gray-700">{enrollment.class?.name || '-'}</span>,
    },
    {
      key: 'level',
      label: translations.level,
      render: (enrollment: Enrollment) => (
        <span className="text-gray-700">{enrollment.class?.level?.name || '-'}</span>
      ),
    },
    {
      key: 'enrolled_date',
      label: translations.enrolledOn,
      render: (enrollment: Enrollment) => <span className="text-gray-700">{formatDate(enrollment.enrolled_date)}</span>,
    },
    {
      key: 'completed_date',
      label: translations.completedOn,
      render: (enrollment: Enrollment) => (
        <span className="text-gray-700">
          {enrollment.status === 'completed' ? formatDate(enrollment.enrolled_date) : translations.notAvailable}
        </span>
      ),
    },
    {
      key: 'status',
      label: translations.status,
      render: (enrollment: Enrollment) => getStatusBadge(enrollment.status),
    },
  ];

  return (
    <AuthenticatedLayout title={translations.title}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          <Button
            variant="outline"
            size="sm"
            onClick={() => router.visit(route('student.mcd.enrollment.show'))}
          >
            {translations.backToCurrent}
          </Button>
        }
      >
        {enrollments.length === 0 ? (
          <EmptyState icon={<ClockIcon className="w-12 h-12" />} title={translations.emptyTitle} subtitle={translations.emptySubtitle} />
        ) : (
          <DataTable
            data={{ data: enrollments, current_page: 1, last_page: 1, per_page: enrollments.length, total: enrollments.length, first_page_url: '', from: 1, last_page_url: '', next_page_url: null, path: '', prev_page_url: null, to: enrollments.length, links: [] }}
            config={{
              columns,
              emptyState: {
                title: translations.emptyTitle,
                subtitle: translations.emptySubtitle
              }
            }}
          />
        )}
      </Section>
    </AuthenticatedLayout>
  );
}
