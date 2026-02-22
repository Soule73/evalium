import { useState, useCallback } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type Assessment,
    type AssessmentAssignment,
    type Answer,
    type User,
    type Question,
    type PageProps,
    type AssessmentRouteContext,
} from '@/types';
import { requiresManualGrading } from '@/utils';
import { route } from 'ziggy-js';
import { router, usePage } from '@inertiajs/react';
import { Button, Section, Textarea, ConfirmationModal, Stat, QuestionList } from '@/Components';
import { FileList } from '@/Components/shared/lists';
import { hasPermission } from '@/utils';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAssessmentReview } from '@/hooks/features/assessment';
import { AssessmentContextInfo, AssessmentHeader } from '@/Components/features/assessment';
import {
    DocumentTextIcon,
    ChartPieIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';

interface GradingState {
    allowed: boolean;
    reason: string;
    warning: string | null;
}

interface Props {
    assessment: Assessment;
    student: User;
    assignment: AssessmentAssignment;
    userAnswers: Record<number, Answer>;
    fileAnswers?: Answer[];
    gradingState: GradingState;
    routeContext: AssessmentRouteContext;
}

export default function GradeAssignment({
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
    const { auth } = usePage<PageProps>().props;
    const canGradeAssessments = hasPermission(auth.permissions, 'grade assessments');

    const [editableScores, setEditableScores] = useState<Record<number, number>>(() => {
        const initialScores: Record<number, number> = {};
        (assessment.questions ?? []).forEach((question) => {
            const answer = userAnswers[question.id];
            initialScores[question.id] = answer?.score ?? 0;
        });
        return initialScores;
    });

    const [feedbacks, setFeedbacks] = useState<Record<number, string>>(() => {
        const initialFeedbacks: Record<number, string> = {};
        (assessment.questions ?? []).forEach((question) => {
            const answer = userAnswers[question.id];
            if (answer?.feedback) {
                initialFeedbacks[question.id] = answer.feedback;
            }
        });
        return initialFeedbacks;
    });

    const [teacherNotes, setTeacherNotes] = useState(assignment.teacher_notes || '');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);

    const { totalPoints, calculatedTotalScore, percentage, scores, getQuestionResult } =
        useAssessmentReview({
            assessment,
            userAnswers,
            scoreOverrides: editableScores,
            feedbackOverrides: feedbacks,
        });

    const handleScoreChange = useCallback((questionId: number, value: string) => {
        const numValue = parseFloat(value) || 0;
        setEditableScores((prev) => ({ ...prev, [questionId]: numValue }));
    }, []);

    const handleFeedbackChange = useCallback((questionId: number, value: string) => {
        setFeedbacks((prev) => ({ ...prev, [questionId]: value }));
    }, []);

    const handleSubmit = useCallback(() => {
        setShowConfirmModal(true);
    }, []);

    const saveGradeUrl = route(routeContext.saveGradeRoute, {
        assessment: assessment.id,
        assignment: assignment.id,
    });

    const handleConfirmSubmit = useCallback(() => {
        setIsSubmitting(true);
        setShowConfirmModal(false);

        const scoresWithFeedback = (assessment.questions ?? []).map((question) => ({
            question_id: question.id,
            score: editableScores[question.id] || 0,
            feedback: feedbacks[question.id] || null,
        }));

        router.post(
            saveGradeUrl,
            {
                scores: scoresWithFeedback,
                teacher_notes: teacherNotes,
            },
            {
                onFinish: () => setIsSubmitting(false),
            },
        );
    }, [assessment, editableScores, feedbacks, teacherNotes, saveGradeUrl]);

    const renderScoreInput = useCallback(
        (question: Question) => {
            const questionScore = editableScores[question.id] || 0;
            const maxScore = question.points || 0;
            const isAutoGraded = !requiresManualGrading(question);

            return (
                <div className="mt-4 p-4 bg-gray-50 rounded-lg space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <label className="text-sm font-medium text-gray-700">
                                {t('grading_pages.show.question_score_label', { max: maxScore })}
                            </label>
                            {isAutoGraded && (
                                <p className="text-xs text-indigo-600 mt-1">
                                    {t('grading_pages.show.auto_graded_info')}
                                </p>
                            )}
                            {!isAutoGraded && (
                                <p className="text-xs text-orange-600 mt-1">
                                    {t('grading_pages.show.manual_grading_required')}
                                </p>
                            )}
                        </div>
                        <div className="flex items-center space-x-2">
                            <input
                                type="number"
                                min="0"
                                max={maxScore}
                                step="0.5"
                                value={questionScore}
                                onChange={(e) => handleScoreChange(question.id, e.target.value)}
                                className="w-20 px-2 py-1 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                disabled={!canGradeAssessments}
                            />
                            <span className="text-sm text-gray-500">/ {maxScore}</span>
                        </div>
                    </div>

                    <div>
                        <Textarea
                            label={t('grading_pages.show.question_comment_label')}
                            value={feedbacks[question.id] || ''}
                            onChange={(e) => handleFeedbackChange(question.id, e.target.value)}
                            placeholder={t('grading_pages.show.question_comment_placeholder')}
                            rows={2}
                            disabled={!canGradeAssessments}
                        />
                    </div>
                </div>
            );
        },
        [
            editableScores,
            feedbacks,
            canGradeAssessments,
            handleScoreChange,
            handleFeedbackChange,
            t,
        ],
    );

    const pageBreadcrumbs = breadcrumbs.assessment.grade(routeContext, assessment, student);

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

    return (
        <AuthenticatedLayout
            title={t('grading_pages.show.title', {
                student: student.name,
                assessment: assessment.title,
            })}
            breadcrumb={pageBreadcrumbs}
        >
            <div className="max-w-6xl mx-auto space-y-6">
                {gradingState.warning === 'grading_without_submission' && (
                    <div className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <ExclamationTriangleIcon className="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                        <div>
                            <p className="font-medium text-amber-800">
                                {t('grading_pages.show.warning_not_submitted_ended_title')}
                            </p>
                            <p className="mt-1 text-sm text-amber-700">
                                {t('grading_pages.show.warning_not_submitted_ended_message')}
                            </p>
                        </div>
                    </div>
                )}

                <Section title={assessment.title} collapsible defaultOpen={false}>
                    <AssessmentHeader assessment={assessment} showDescription showMetadata />
                </Section>

                <Section
                    title={t('grading_pages.show.correction_title', { student: student.name })}
                    actions={
                        <div className="flex items-center space-x-4">
                            <Button
                                onClick={() => router.visit(backUrl)}
                                variant="outline"
                                size="sm"
                            >
                                {t('grading_pages.show.back_to_assessment')}
                            </Button>
                            {canGradeAssessments && (
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
                    <Stat.Group columns={3}>
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
                                assignment.submitted_at
                                    ? t('grading_pages.show.submitted')
                                    : t('grading_pages.show.not_submitted')
                            }
                            icon={assignment.submitted_at ? CheckCircleIcon : XCircleIcon}
                        />
                    </Stat.Group>
                </Section>

                <QuestionList
                    title={t('grading_pages.show.questions_correction')}
                    questions={assessment.questions ?? []}
                    getQuestionResult={getQuestionResult}
                    scores={scores}
                    isEditMode={canGradeAssessments}
                    renderScoreInput={renderScoreInput}
                />

                {fileAnswers.length > 0 && (
                    <Section title={t('grading_pages.show.student_files_title')}>
                        <FileList attachments={fileAnswers} readOnly />
                    </Section>
                )}

                <Section title={t('grading_pages.show.teacher_notes_label')}>
                    <Textarea
                        value={teacherNotes}
                        onChange={(e) => setTeacherNotes(e.target.value)}
                        placeholder={t('grading_pages.show.teacher_notes_placeholder')}
                        rows={4}
                        disabled={!canGradeAssessments}
                        helperText={t('grading_pages.show.teacher_notes_help')}
                    />
                </Section>
            </div>

            <ConfirmationModal
                isOpen={showConfirmModal}
                onClose={() => setShowConfirmModal(false)}
                onConfirm={handleConfirmSubmit}
                title={t('grading_pages.show.confirm_save_title')}
                message={t('grading_pages.show.confirm_save_message', { student: student.name })}
                confirmText={t('grading_pages.show.confirm_save')}
                cancelText={t('grading_pages.show.cancel')}
                type="info"
                loading={isSubmitting}
            />
        </AuthenticatedLayout>
    );
}
