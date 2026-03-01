import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type Assessment,
    type AssessmentAssignment,
    type Answer,
    type User,
    type PageProps,
    type AssessmentRouteContext,
} from '@/types';
import { router } from '@inertiajs/react';
import {
    Button,
    Section,
    Textarea,
    ConfirmationModal,
    QuestionList,
    AlertEntry,
} from '@/Components';
import { FileList } from '@/Components/shared/lists';
import { hasPermission } from '@/utils';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAssessmentReview, useGradeState } from '@/hooks/features/assessment';
import {
    AssessmentContextInfo,
    AssessmentHeader,
    AssignmentScoreStats,
} from '@/Components/features/assessment';
import { QuestionProvider } from '@/Components/features/assessment/question';
import { ArrowPathIcon } from '@heroicons/react/24/outline';
import { ReassignModal } from '@/Components/features/assessment/ReassignModal';
import { useState } from 'react';

interface GradingState {
    allowed: boolean;
    reason: string;
    warning: string | null;
    no_responses: boolean;
    correction_locked: boolean;
    can_reassign: boolean;
    reassign_reason: string | null;
}

interface Props extends PageProps {
    assessment: Assessment;
    student: User;
    assignment: AssessmentAssignment;
    userAnswers: Record<number, Answer>;
    fileAnswers?: Answer[];
    gradingState: GradingState;
    routeContext: AssessmentRouteContext;
}

export default function GradeAssignment({
    auth,
    assessment,
    student,
    assignment,
    userAnswers = {},
    fileAnswers = [],
    gradingState,
    routeContext,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canGradeAssessments = hasPermission(auth.permissions, 'grade assessments');
    const [showReassignModal, setShowReassignModal] = useState(false);

    const isLocked = gradingState.correction_locked;
    const canReassign = gradingState.can_reassign && !!routeContext?.reassignRoute;

    const {
        editableScores,
        setEditableScores,
        feedbacks,
        teacherNotes,
        setTeacherNotes,
        isSubmitting,
        showConfirmModal,
        setShowConfirmModal,
        backUrl,
        handleFeedbackChange,
        handleSubmit,
        handleConfirmSubmit,
    } = useGradeState({ assessment, assignment, userAnswers, routeContext });

    const { totalPoints, calculatedTotalScore, percentage } = useAssessmentReview({
        assessment,
        userAnswers,
        scoreOverrides: editableScores,
    });

    const pageBreadcrumbs = breadcrumbs.assessment.grade(routeContext, assessment, student);

    return (
        <AuthenticatedLayout
            title={t('grading_pages.show.title', {
                student: student.name,
                assessment: assessment.title,
            })}
            breadcrumb={pageBreadcrumbs}
        >
            {isLocked && (
                <AlertEntry
                    type="error"
                    title={t('grading_pages.show.no_responses_title')}
                    className="mb-6"
                >
                    <p>{t('grading_pages.show.no_responses_message')}</p>
                    {canReassign && (
                        <Button
                            onClick={() => setShowReassignModal(true)}
                            variant="outline"
                            size="sm"
                            className="mt-3"
                        >
                            <ArrowPathIcon className="h-4 w-4 mr-2" />
                            {t('grading_pages.show.reassign_button')}
                        </Button>
                    )}
                    {!canReassign &&
                        gradingState.reassign_reason === 'supervised_questions_exposed' && (
                            <p className="mt-2 text-xs">
                                {t('grading_pages.show.reassign_blocked_supervised')}
                            </p>
                        )}
                </AlertEntry>
            )}

            {!isLocked && gradingState.warning === 'grading_without_submission' && (
                <AlertEntry
                    type="warning"
                    title={t('grading_pages.show.warning_not_submitted_ended_title')}
                    className="mb-6"
                >
                    <p>{t('grading_pages.show.warning_not_submitted_ended_message')}</p>
                </AlertEntry>
            )}

            <Section title={assessment.title} collapsible defaultOpen={false}>
                <AssessmentHeader assessment={assessment} showDescription showMetadata />
            </Section>

            <Section
                title={t('grading_pages.show.correction_title', { student: student.name })}
                actions={
                    <div className="flex items-center space-x-4">
                        <Button onClick={() => router.visit(backUrl)} variant="outline" size="sm">
                            {t('grading_pages.show.back_to_assessment')}
                        </Button>
                        {canGradeAssessments && !isLocked && (
                            <Button
                                onClick={handleSubmit}
                                disabled={isSubmitting}
                                loading={isSubmitting}
                                size="sm"
                            >
                                {isSubmitting
                                    ? t('grading_pages.show.saving')
                                    : t('grading_pages.show.save_grades')}
                            </Button>
                        )}
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
                />
            </Section>

            <Section title={t('grading_pages.show.teacher_notes_label')}>
                <Textarea
                    value={teacherNotes}
                    onChange={(e) => setTeacherNotes(e.target.value)}
                    placeholder={t('grading_pages.show.teacher_notes_placeholder')}
                    rows={4}
                    disabled={!canGradeAssessments || isLocked}
                    helperText={t('grading_pages.show.teacher_notes_help')}
                />
            </Section>

            <QuestionProvider
                mode={isLocked ? 'review' : 'grade'}
                role={routeContext?.role ?? 'teacher'}
                userAnswers={userAnswers}
                canEditScores={canGradeAssessments && !isLocked}
                scoreOverrides={editableScores}
                onScoreChange={(questionId, value) =>
                    setEditableScores((prev) => ({ ...prev, [questionId]: value }))
                }
                feedbackOverrides={feedbacks}
                onFeedbackChange={handleFeedbackChange}
            >
                <QuestionList
                    title={t('grading_pages.show.questions_correction')}
                    questions={assessment.questions ?? []}
                />
            </QuestionProvider>

            {fileAnswers.length > 0 && (
                <Section title={t('grading_pages.show.student_files_title')}>
                    <FileList attachments={fileAnswers} readOnly />
                </Section>
            )}
            {!isLocked && (
                <ConfirmationModal
                    isOpen={showConfirmModal}
                    onClose={() => setShowConfirmModal(false)}
                    onConfirm={handleConfirmSubmit}
                    title={t('grading_pages.show.confirm_save_title')}
                    message={t('grading_pages.show.confirm_save_message', {
                        student: student.name,
                    })}
                    confirmText={t('grading_pages.show.confirm_save')}
                    cancelText={t('grading_pages.show.cancel')}
                    type="info"
                    loading={isSubmitting}
                />
            )}

            {canReassign && (
                <ReassignModal
                    isOpen={showReassignModal}
                    onClose={() => setShowReassignModal(false)}
                    assessment={assessment}
                    assignment={assignment}
                    student={student}
                    routeContext={routeContext}
                />
            )}
        </AuthenticatedLayout>
    );
}
