import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type Assessment,
    type AssessmentAssignment,
    type Answer,
    type User,
    type AssessmentRouteContext,
} from '@/types';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { Badge, Button, Section, Stat, QuestionList, TeacherNotesDisplay } from '@/Components';
import { FileList } from '@/Components/shared/lists';
import { formatDate } from '@/utils';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAssessmentReview } from '@/hooks/features/assessment';
import { AssessmentContextInfo, AssessmentHeader } from '@/Components/features/assessment';
import { DocumentTextIcon, ChartPieIcon, CheckCircleIcon } from '@heroicons/react/24/outline';

interface Props {
    assessment: Assessment;
    student: User;
    assignment: AssessmentAssignment;
    userAnswers: Record<number, Answer>;
    fileAnswers?: Answer[];
    routeContext: AssessmentRouteContext;
}

export default function ReviewAssignment({
    assessment,
    student,
    assignment,
    userAnswers = {},
    fileAnswers = [],
    routeContext,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const { totalPoints, calculatedTotalScore, percentage, scores, getQuestionResult } =
        useAssessmentReview({ assessment, userAnswers });

    const pageBreadcrumbs = breadcrumbs.assessment.review(routeContext, assessment, student);

    const backUrl = (() => {
        if (routeContext.showRoute) return route(routeContext.showRoute, assessment.id);
        const classId = assessment.class_subject?.class?.id;
        if (classId && routeContext.classAssessmentShowRoute) {
            return route(routeContext.classAssessmentShowRoute, {
                class: classId,
                assessment: assessment.id,
            });
        }
        return route(routeContext.backRoute);
    })();

    const gradeRouteUrl = route(routeContext.gradeRoute, {
        assessment: assessment.id,
        assignment: assignment.id,
    });

    return (
        <AuthenticatedLayout
            title={t('grading_pages.review.title', {
                student: student.name,
                assessment: assessment.title,
            })}
            breadcrumb={pageBreadcrumbs}
        >
            <div className="max-w-6xl mx-auto space-y-6">
                <Section title={assessment.title} collapsible defaultOpen={false}>
                    <AssessmentHeader assessment={assessment} showDescription showMetadata />
                </Section>

                <Section
                    title={t('grading_pages.review.result_title', { student: student.name })}
                    actions={
                        <div className="flex items-center space-x-4">
                            <Button
                                onClick={() => router.visit(backUrl)}
                                variant="outline"
                                size="sm"
                            >
                                {t('grading_pages.show.back_to_assessment')}
                            </Button>
                            <Button onClick={() => router.visit(gradeRouteUrl)} size="sm">
                                {t('grading_pages.review.edit_grades')}
                            </Button>
                        </div>
                    }
                >
                    <AssessmentContextInfo
                        assessment={assessment}
                        role={routeContext?.role ?? 'teacher'}
                        student={student}
                    />
                    <div className="my-4 border-t border-gray-100" />
                    <Stat.Group columns={4}>
                        <Stat.Item
                            title={t('grading_pages.show.total_score')}
                            value={`${calculatedTotalScore} / ${totalPoints}`}
                            icon={DocumentTextIcon}
                        />
                        <Stat.Item
                            title={t('grading_pages.show.percentage')}
                            value={`${percentage}%`}
                            icon={ChartPieIcon}
                        />
                        <Stat.Item
                            title={t('grading_pages.show.status')}
                            value={
                                <Badge
                                    size="sm"
                                    label={
                                        assignment.submitted_at
                                            ? t('grading_pages.show.submitted')
                                            : t('grading_pages.show.not_submitted')
                                    }
                                    type={assignment.submitted_at ? 'success' : 'gray'}
                                />
                            }
                            icon={CheckCircleIcon}
                        />
                        <Stat.Item
                            title={t('grading_pages.review.graded_at')}
                            value={
                                assignment.graded_at
                                    ? formatDate(assignment.graded_at, 'datetime')
                                    : '-'
                            }
                            icon={DocumentTextIcon}
                        />
                    </Stat.Group>
                </Section>

                <QuestionList
                    title={t('grading_pages.review.questions_review')}
                    questions={assessment.questions ?? []}
                    getQuestionResult={getQuestionResult}
                    scores={scores}
                />

                {fileAnswers.length > 0 && (
                    <Section title={t('grading_pages.show.student_files_title')}>
                        <FileList attachments={fileAnswers} readOnly />
                    </Section>
                )}

                <TeacherNotesDisplay
                    notes={assignment.teacher_notes}
                    title={t('grading_pages.show.teacher_notes_label')}
                />
            </div>
        </AuthenticatedLayout>
    );
}
