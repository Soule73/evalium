import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Badge, Button, Section, Stat, QuestionReviewList, TeacherNotesDisplay } from '@/Components';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAssessmentReview } from '@/hooks/features/assessment';
import { formatDate } from '@/utils';
import type { Enrollment, Assessment, AssessmentAssignment, Answer, PageProps } from '@/types';
import { DocumentTextIcon, ChartPieIcon, CheckCircleIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  enrollment: Enrollment;
  assignment: AssessmentAssignment;
  assessment: Assessment;
  userAnswers: Record<number, Answer>;
}

export default function EnrollmentAssignmentShow({ enrollment, assignment, assessment, userAnswers = {} }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const { totalPoints, calculatedTotalScore, percentage, scores, getQuestionResult } =
    useAssessmentReview({ assessment, userAnswers });

  const pageBreadcrumbs = [
    ...breadcrumbs.admin.showEnrollment(enrollment),
    {
      label: t('breadcrumbs.enrollment_assignments'),
      href: route('admin.enrollments.assignments', enrollment.id),
    },
    { label: assessment.title },
  ];

  return (
    <AuthenticatedLayout
      title={t('admin_pages.enrollments.assignment_show_title')}
      breadcrumb={pageBreadcrumbs}
    >
      <div className="max-w-6xl mx-auto space-y-6">
        <Section
          title={assessment.title}
          subtitle={t('admin_pages.enrollments.assignment_show_subtitle')}
          actions={
            <Button
              size="sm"
              variant="outline"
              color="secondary"
              onClick={() => router.visit(route('admin.enrollments.assignments', enrollment.id))}
            >
              {t('admin_pages.enrollments.back_to_assignments')}
            </Button>
          }
        >
          <Stat.Group columns={4}>
            <Stat.Item
              title={t('admin_pages.enrollments.score')}
              value={assignment.score !== null && assignment.score !== undefined
                ? `${calculatedTotalScore} / ${totalPoints}`
                : '-'}
              icon={DocumentTextIcon}
            />
            <Stat.Item
              title={t('grading_pages.show.percentage')}
              value={assignment.score !== null ? `${percentage}%` : '-'}
              icon={ChartPieIcon}
            />
            <Stat.Item
              title={t('admin_pages.enrollments.assignment_status')}
              value={
                <Badge
                  size="sm"
                  label={assignment.submitted_at
                    ? (assignment.graded_at
                      ? t('components.assignment_list.status_graded')
                      : t('components.assignment_list.status_pending_grading'))
                    : t('components.assignment_list.status_not_started')}
                  type={assignment.graded_at ? 'success' : (assignment.submitted_at ? 'warning' : 'gray')}
                />
              }
              icon={CheckCircleIcon}
            />
            <Stat.Item
              title={t('admin_pages.enrollments.submitted_at')}
              value={assignment.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
              icon={CalendarIcon}
            />
          </Stat.Group>
        </Section>

        <QuestionReviewList
          title={t('grading_pages.review.questions_review')}
          questions={assessment.questions ?? []}
          getQuestionResult={getQuestionResult}
          scores={scores}
        />

        <TeacherNotesDisplay
          notes={assignment.teacher_notes}
          title={t('grading_pages.show.teacher_notes_label')}
        />
      </div>
    </AuthenticatedLayout>
  );
}
