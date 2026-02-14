import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, PageProps, PaginationType } from '@/types';
import { Badge, Button, DataTable, Section, Stat, TextEntry } from '@/Components';
import type { DataTableConfig } from '@/types/datatable';
import { trans, formatDate } from '@/utils';
import { breadcrumbs } from '@/utils/helpers/breadcrumbs';
import { AcademicCapIcon, BookOpenIcon } from '@heroicons/react/24/outline';

interface SubjectGrade {
  id: number;
  class_subject_id: number;
  subject_name: string;
  teacher_name: string;
  coefficient: number;
  average: number | null;
  assessments_count: number;
  completed_count: number;
}

interface OverallStats {
  student_id: number;
  student_name: string;
  class_id: number;
  class_name: string;
  subjects: SubjectGrade[];
  annual_average: number | null;
  total_coefficient: number;
}

interface StudentEnrollmentShowProps extends PageProps {
  enrollment: Enrollment;
  subjects: PaginationType<SubjectGrade>;
  overallStats: OverallStats;
}

export default function Show({ enrollment, subjects, overallStats }: StudentEnrollmentShowProps) {
  const translations = {
    title: trans('student_enrollment_pages.show.title'),
    subtitle: trans('student_enrollment_pages.show.subtitle'),
    currentClass: trans('student_enrollment_pages.show.current_class'),
    enrolledOn: trans('student_enrollment_pages.show.enrolled_on'),
    academicYear: trans('student_enrollment_pages.show.academic_year'),
    level: trans('student_enrollment_pages.show.level'),
    className: trans('student_enrollment_pages.show.class_name'),
    gradeBreakdown: trans('student_enrollment_pages.show.grade_breakdown'),
    subject: trans('student_enrollment_pages.show.subject'),
    teacher: trans('student_enrollment_pages.show.teacher'),
    average: trans('student_enrollment_pages.show.average'),
    assessments: trans('student_enrollment_pages.show.assessments'),
    noGrade: trans('student_enrollment_pages.show.no_grade'),
    overallStatistics: trans('student_enrollment_pages.show.overall_statistics'),
    overallAverage: trans('student_enrollment_pages.show.overall_average'),
    totalAssessments: trans('student_enrollment_pages.show.total_assessments'),
    completedAssessments: trans('student_enrollment_pages.show.completed_assessments'),
    pendingAssessments: trans('student_enrollment_pages.show.pending_assessments'),
    viewHistory: trans('student_enrollment_pages.show.view_history'),
    viewClassmates: trans('student_enrollment_pages.show.view_classmates'),
    searchPlaceholder: trans('common.search'),
    excellent: trans('student_enrollment_pages.show.excellent'),
    veryGood: trans('student_enrollment_pages.show.very_good'),
    good: trans('student_enrollment_pages.show.good'),
    satisfactory: trans('student_enrollment_pages.show.satisfactory'),
    needsImprovement: trans('student_enrollment_pages.show.needs_improvement'),
  };

  const totalAssessments = useMemo(
    () => overallStats?.subjects?.reduce((sum, item) => sum + (item.assessments_count || 0), 0) || 0,
    [overallStats]
  );

  const completedAssessments = useMemo(
    () => overallStats?.subjects?.reduce((sum, item) => sum + (item.completed_count || 0), 0) || 0,
    [overallStats]
  );

  const getGradeColor = (average: number | null) => {
    if (average === null) return 'text-gray-500';
    if (average >= 16) return 'text-green-600 font-semibold';
    if (average >= 14) return 'text-blue-600 font-semibold';
    if (average >= 12) return 'text-yellow-600 font-medium';
    if (average >= 10) return 'text-orange-600 font-medium';
    return 'text-red-600 font-semibold';
  };

  const gradeThresholds = [
    { threshold: 16, label: translations.excellent, type: 'success' as const },
    { threshold: 14, label: translations.veryGood, type: 'success' as const },
    { threshold: 12, label: translations.good, type: 'info' as const },
    { threshold: 10, label: translations.satisfactory, type: 'warning' as const },
    { threshold: 0, label: translations.needsImprovement, type: 'error' as const },
  ];

  const getGradeLabel = useMemo(() => (average: number | null) => {
    if (average === null) return null;
    const gradeInfo = gradeThresholds.find(({ threshold }) => average >= threshold);

    return gradeInfo ? <Badge label={gradeInfo.label} type={gradeInfo.type} size='sm' /> : null;
  }, [gradeThresholds]);

  const subjectsTableConfig: DataTableConfig<SubjectGrade> = {
    columns: [
      {
        key: 'subject_name',
        label: translations.subject,
        sortable: true,
      },
      {
        key: 'teacher_name',
        label: translations.teacher,
        sortable: true,
        render: (item: SubjectGrade) => item.teacher_name || '-',
      },
      {
        key: 'assessments',
        label: translations.assessments,
        sortable: false,
        render: (item: SubjectGrade) => (
          <span className="text-sm text-gray-600">
            {item.completed_count} / {item.assessments_count}
          </span>
        ),
      },
      {
        key: 'average',
        label: translations.average,
        sortable: true,
        render: (item: SubjectGrade) => (
          <div className="flex items-center justify-start space-x-2">
            <span className={`${getGradeColor(item.average)}`}>
              {item.average != null
                ? `${Number(item.average).toFixed(2)}/20`
                : '-'}
            </span>
            {getGradeLabel(item.average)}
          </div>
        ),
      },
    ],
    searchPlaceholder: translations.searchPlaceholder,
    perPageOptions: [10, 25, 50],
    emptyState: {
      icon: <BookOpenIcon className="w-12 h-12" />,
      title: trans('student_enrollment_pages.show.no_subjects_title'),
      subtitle: trans('student_enrollment_pages.show.no_subjects_subtitle'),
    },
    emptySearchState: {
      icon: <BookOpenIcon className="w-12 h-12" />,
      title: trans('common.no_search_results'),
      subtitle: trans('common.try_different_search'),
    },
  };

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.student.enrollment()}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          <div className="flex items-center space-x-4">
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.visit(route('student.enrollment.history'))}
            >
              {translations.viewHistory}
            </Button>
            <Button size="sm" onClick={() => router.visit(route('student.enrollment.classmates'))}>
              {translations.viewClassmates}
            </Button>
          </div>
        }
      >
        <h3 className="text-lg font-semibold text-gray-900 mb-4">{translations.currentClass}</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <TextEntry label={translations.className} value={enrollment.class?.name || '-'} />
          <TextEntry label={translations.level} value={enrollment.class?.level?.name || '-'} />
          <TextEntry
            label={translations.academicYear}
            value={enrollment.class?.academic_year?.name || '-'}
          />
          <TextEntry label={translations.enrolledOn} value={formatDate(enrollment.enrolled_at)} />
        </div>

      </Section>
      <Section title={translations.overallStatistics}>
        <Stat.Group columns={4}>
          <Stat.Item
            title={translations.overallAverage}
            value={overallStats.annual_average !== null ? `${overallStats.annual_average.toFixed(2)}/20` : translations.noGrade}
            icon={AcademicCapIcon}
          />
          <Stat.Item
            title={translations.totalAssessments}
            value={totalAssessments || 0}
            icon={BookOpenIcon}
          />
          <Stat.Item
            title={translations.completedAssessments}
            value={completedAssessments || 0}
            icon={BookOpenIcon}
          />
          <Stat.Item
            title={translations.pendingAssessments}
            value={(totalAssessments - completedAssessments) || 0}
            icon={BookOpenIcon}
          />
        </Stat.Group>
      </Section>

      <Section title={translations.gradeBreakdown}>
        <DataTable data={subjects} config={subjectsTableConfig} />

      </Section>

    </AuthenticatedLayout>
  );
}
