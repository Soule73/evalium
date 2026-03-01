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
import { Button, Section, QuestionList, TeacherNotesDisplay } from '@/Components';
import { FileList } from '@/Components/shared/lists';
import { QuestionProvider } from '@/Components/features/assessment/question';
import { getAssessmentBackUrl } from '@/utils';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAssessmentReview } from '@/hooks/features/assessment';
import {
    AssessmentContextInfo,
    AssessmentHeader,
    AssignmentScoreStats,
} from '@/Components/features/assessment';

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

    const { totalPoints, calculatedTotalScore, percentage } = useAssessmentReview({
        assessment,
        userAnswers,
    });

    const pageBreadcrumbs = breadcrumbs.assessment.review(routeContext, assessment, student);

    const backUrl = getAssessmentBackUrl(routeContext, assessment);

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
            <div className="space-y-6">
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
                    <AssignmentScoreStats
                        calculatedTotalScore={calculatedTotalScore}
                        totalPoints={totalPoints}
                        percentage={percentage}
                        assignment={assignment}
                        showGradedAt
                    />
                </Section>

                <QuestionProvider
                    mode="review"
                    role={routeContext?.role ?? 'teacher'}
                    userAnswers={userAnswers}
                >
                    <QuestionList
                        title={t('grading_pages.review.questions_review')}
                        questions={assessment.questions ?? []}
                    />
                </QuestionProvider>

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
