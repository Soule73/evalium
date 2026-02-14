import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, PageProps, PaginationType, SubjectGrade, OverallStats } from '@/types';
import { Button, Section, TextEntry } from '@/Components';
import { SubjectGradeList } from '@/Components/shared/lists/SubjectGradeList';
import { trans, formatDate } from '@/utils';
import { breadcrumbs } from '@/utils/helpers/breadcrumbs';

interface StudentEnrollmentShowProps extends PageProps {
  enrollment: Enrollment;
  subjects: PaginationType<SubjectGrade>;
  overallStats: OverallStats;
}

export default function Show({ enrollment, subjects, overallStats }: StudentEnrollmentShowProps) {
  return (
    <AuthenticatedLayout
      title={trans('student_enrollment_pages.show.title')}
      breadcrumb={breadcrumbs.student.enrollment()}
    >
      <Section
        title={trans('student_enrollment_pages.show.title')}
        subtitle={trans('student_enrollment_pages.show.subtitle')}
        actions={
          <div className="flex items-center space-x-4">
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.visit(route('student.enrollment.history'))}
            >
              {trans('student_enrollment_pages.show.view_history')}
            </Button>
            <Button size="sm" onClick={() => router.visit(route('student.enrollment.classmates'))}>
              {trans('student_enrollment_pages.show.view_classmates')}
            </Button>
          </div>
        }
      >
        <h3 className="text-lg font-semibold text-gray-900 mb-4">
          {trans('student_enrollment_pages.show.current_class')}
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <TextEntry
            label={trans('student_enrollment_pages.show.class_name')}
            value={enrollment.class?.name || '-'}
          />
          <TextEntry
            label={trans('student_enrollment_pages.show.level')}
            value={enrollment.class?.level?.name || '-'}
          />
          <TextEntry
            label={trans('student_enrollment_pages.show.academic_year')}
            value={enrollment.class?.academic_year?.name || '-'}
          />
          <TextEntry
            label={trans('student_enrollment_pages.show.enrolled_on')}
            value={formatDate(enrollment.enrolled_at)}
          />
        </div>
      </Section>

      <SubjectGradeList subjects={subjects} overallStats={overallStats} variant="student" showSearch />
    </AuthenticatedLayout>
  );
}
