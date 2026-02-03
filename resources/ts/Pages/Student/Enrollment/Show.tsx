import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, PageProps } from '@/types';
import { Badge, Button, EmptyState, Section, StatCard, TextEntry } from '@/Components';
import { trans, formatDate } from '@/utils';
import { AcademicCapIcon, BookOpenIcon } from '@heroicons/react/24/outline';

interface StudentEnrollmentShowProps extends PageProps {
  enrollment: Enrollment;
  gradeBreakdown: {
    subject_id: number;
    subject_name: string;
    teacher_name: string;
    average: number | null;
    assessments_count: number;
    completed_count: number;
  }[];
}

export default function Show({ enrollment, gradeBreakdown }: StudentEnrollmentShowProps) {
  const translations = useMemo(
    () => ({
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
    }),
    []
  );

  const overallAverage = useMemo(() => {
    const validGrades = gradeBreakdown.filter((item) => item.average !== null);
    if (validGrades.length === 0) return null;
    return validGrades.reduce((sum, item) => sum + (item.average || 0), 0) / validGrades.length;
  }, [gradeBreakdown]);

  const totalAssessments = useMemo(
    () => gradeBreakdown.reduce((sum, item) => sum + item.assessments_count, 0),
    [gradeBreakdown]
  );

  const completedAssessments = useMemo(
    () => gradeBreakdown.reduce((sum, item) => sum + item.completed_count, 0),
    [gradeBreakdown]
  );

  const getGradeColor = (average: number | null) => {
    if (average === null) return 'text-gray-500';
    if (average >= 16) return 'text-green-600 font-semibold';
    if (average >= 14) return 'text-blue-600 font-semibold';
    if (average >= 12) return 'text-yellow-600 font-medium';
    if (average >= 10) return 'text-orange-600 font-medium';
    return 'text-red-600 font-semibold';
  };

  const getGradeLabel = (average: number | null) => {
    if (average === null) return null;
    if (average >= 16) return <Badge label={trans('student_enrollment_pages.show.excellent')} type="success" />;
    if (average >= 14) return <Badge label={trans('student_enrollment_pages.show.very_good')} type="success" />;
    if (average >= 12) return <Badge label={trans('student_enrollment_pages.show.good')} type="info" />;
    if (average >= 10) return <Badge label={trans('student_enrollment_pages.show.satisfactory')} type="warning" />;
    return <Badge label={trans('student_enrollment_pages.show.needs_improvement')} type="error" />;
  };

  return (
    <AuthenticatedLayout title={translations.title}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          <div className="flex items-center space-x-4">
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.visit(route('student.mcd.enrollment.history'))}
            >
              {translations.viewHistory}
            </Button>
            <Button size="sm" onClick={() => router.visit(route('student.mcd.enrollment.classmates'))}>
              {translations.viewClassmates}
            </Button>
          </div>
        }
      >
        <div className="bg-white rounded-lg border border-gray-200 p-6 mb-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">{translations.currentClass}</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <TextEntry label={translations.className} value={enrollment.class?.name || '-'} />
            <TextEntry label={translations.level} value={enrollment.class?.level?.name || '-'} />
            <TextEntry
              label={translations.academicYear}
              value={enrollment.class?.academic_year?.name || '-'}
            />
          </div>
          <div className="mt-4">
            <TextEntry label={translations.enrolledOn} value={formatDate(enrollment.enrolled_date)} />
          </div>
        </div>

        <div className="mb-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">{translations.overallStatistics}</h3>
          <div className="grid gap-y-2 grid-cols-1 lg:grid-cols-4">
            <StatCard
              title={translations.overallAverage}
              value={overallAverage !== null ? `${overallAverage.toFixed(2)}/20` : translations.noGrade}
              icon={AcademicCapIcon}
              color="blue"
              className="lg:rounded-r-none"
            />
            <StatCard
              title={translations.totalAssessments}
              value={totalAssessments}
              icon={BookOpenIcon}
              color="green"
              className="lg:rounded-none lg:border-x-0"
            />
            <StatCard
              title={translations.completedAssessments}
              value={completedAssessments}
              icon={BookOpenIcon}
              color="purple"
              className="lg:rounded-none lg:border-x-0"
            />
            <StatCard
              title={translations.pendingAssessments}
              value={totalAssessments - completedAssessments}
              icon={BookOpenIcon}
              color="yellow"
              className="lg:rounded-l-none"
            />
          </div>
        </div>

        <div>
          <h3 className="text-lg font-semibold text-gray-900 mb-4">{translations.gradeBreakdown}</h3>
          {gradeBreakdown.length === 0 ? (
            <EmptyState
              icon={<BookOpenIcon className="w-12 h-12" />}
              title={trans('student_enrollment_pages.show.no_enrollment_title')}
              subtitle={trans('student_enrollment_pages.show.no_enrollment_subtitle')}
            />
          ) : (
            <div className="bg-gray-50 rounded-lg overflow-hidden">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-100">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {translations.subject}
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {translations.teacher}
                    </th>
                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {translations.assessments}
                    </th>
                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {translations.average}
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {gradeBreakdown.map((item) => (
                    <tr key={item.subject_id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">
                          {item.subject_name}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-600">{item.teacher_name || '-'}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-center">
                        <div className="text-sm text-gray-600">
                          {item.completed_count} / {item.assessments_count}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-center">
                        <div className="flex items-center justify-center space-x-2">
                          <span className={`text-lg ${getGradeColor(item.average)}`}>
                            {item.average !== null
                              ? `${item.average.toFixed(2)}/20`
                              : translations.noGrade}
                          </span>
                          {getGradeLabel(item.average)}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </Section>
    </AuthenticatedLayout>
  );
}
