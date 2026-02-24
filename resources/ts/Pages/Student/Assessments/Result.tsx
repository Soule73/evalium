import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type AssessmentAssignment, type Answer } from '@/types';
import { useAssessmentResults } from '@/hooks/features/assessment';
import useAssessmentScoring from '@/hooks/features/assessment/useAssessmentScoring';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { AlertEntry, Badge, Button, Section, QuestionList, Stat } from '@/Components';
import { QuestionProvider } from '@/Components/features/assessment/question';
import { AssessmentHeader } from '@/Components/features/assessment';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatDate } from '@/utils';
import {
    DocumentTextIcon,
    ChartPieIcon,
    CheckCircleIcon,
    CalendarDaysIcon,
    UserIcon,
    AcademicCapIcon,
    BookOpenIcon,
} from '@heroicons/react/24/outline';

interface Props {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    userAnswers: Record<number, Answer>;
    canShowCorrectAnswers: boolean;
}

/**
 * Displays the assessment result page for a student after submission.
 *
 * Shows the assessment info (collapsible), a score/status summary section,
 * and the detailed answer review section.
 */
export default function AssessmentResult({
    assessment,
    assignment,
    userAnswers,
    canShowCorrectAnswers = false,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const { isPendingReview, showCorrectAnswers, showResultsImmediately, totalPoints } =
        useAssessmentResults({ assessment, assignment, canShowCorrectAnswers });

    const { finalScore, finalPercentage } = useAssessmentScoring({ assignment, totalPoints });

    const hasScore =
        (assignment.score !== null && assignment.score !== undefined) ||
        (assignment.auto_score !== null && assignment.auto_score !== undefined);

    const scoreValue =
        hasScore || !isPendingReview
            ? `${Number(finalScore || 0).toFixed(2)} / ${totalPoints}`
            : `— / ${totalPoints}`;

    const percentageValue =
        hasScore || !isPendingReview ? `${Number(finalPercentage || 0).toFixed(1)}%` : '—';

    return (
        <AuthenticatedLayout
            title={t('student_assessment_pages.results.title', { assessment: assessment.title })}
            breadcrumb={breadcrumbs.student.assessmentResults(assessment)}
        >
            <div className="max-w-6xl mx-auto space-y-6">
                <Section title={assessment.title} collapsible defaultOpen={false}>
                    <AssessmentHeader assessment={assessment} showDescription showMetadata />
                </Section>

                <Section
                    title={t('student_assessment_pages.results.section_title')}
                    actions={
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('student.assessments.index'))}
                        >
                            {t('student_assessment_pages.results.back_to_assessments')}
                        </Button>
                    }
                >
                    <Stat.Group columns={3}>
                        <Stat.Item
                            title={t('student_assessment_pages.results.subject')}
                            value={assessment.class_subject?.subject?.name || '—'}
                            icon={BookOpenIcon}
                        />
                        <Stat.Item
                            title={t('student_assessment_pages.results.class')}
                            value={
                                assessment.class_subject?.class?.display_name ??
                                assessment.class_subject?.class?.name ??
                                '—'
                            }
                            icon={AcademicCapIcon}
                        />
                        <Stat.Item
                            title={t('student_assessment_pages.results.teacher')}
                            value={assessment.class_subject?.teacher?.name || '—'}
                            icon={UserIcon}
                        />
                    </Stat.Group>

                    <div className="my-4 border-t border-gray-100" />

                    <Stat.Group columns={4}>
                        <Stat.Item
                            title={t('student_assessment_pages.results.your_score')}
                            value={scoreValue}
                            icon={DocumentTextIcon}
                        />
                        <Stat.Item
                            title={t('student_assessment_pages.results.percentage')}
                            value={percentageValue}
                            icon={ChartPieIcon}
                        />
                        <Stat.Item
                            title={t('student_assessment_pages.results.status')}
                            value={
                                <Badge
                                    size="sm"
                                    label={
                                        isPendingReview
                                            ? t('student_assessment_pages.results.pending_review')
                                            : t('student_assessment_pages.results.graded')
                                    }
                                    type={isPendingReview ? 'warning' : 'success'}
                                />
                            }
                            icon={CheckCircleIcon}
                        />
                        <Stat.Item
                            title={t('student_assessment_pages.results.submitted_at')}
                            value={
                                assignment.submitted_at ? formatDate(assignment.submitted_at) : '—'
                            }
                            icon={CalendarDaysIcon}
                        />
                    </Stat.Group>
                </Section>

                <Section title={t('student_assessment_pages.results.answers_detail')}>
                    {assignment.teacher_notes && (
                        <AlertEntry
                            title={t('student_assessment_pages.results.teacher_comments')}
                            type="info"
                            className="mb-6"
                        >
                            <p className="text-sm whitespace-pre-wrap">
                                {assignment.teacher_notes}
                            </p>
                        </AlertEntry>
                    )}

                    {!showCorrectAnswers && !showResultsImmediately && isPendingReview && (
                        <AlertEntry
                            title={t('student_assessment_pages.results.results_hidden_title')}
                            type="warning"
                            className="mb-6"
                        >
                            <p className="text-sm">
                                {t('student_assessment_pages.results.results_hidden_message')}
                            </p>
                        </AlertEntry>
                    )}

                    <QuestionProvider
                        mode="results"
                        role="student"
                        userAnswers={userAnswers}
                        showCorrectAnswers={showCorrectAnswers}
                    >
                        <QuestionList questions={assessment.questions || []} />
                    </QuestionProvider>
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
