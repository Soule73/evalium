import React, { useState, useMemo, useCallback } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, ConfirmationModal, Section, Stat, QuestionList } from '@/Components';
import { useFormatters } from '@/hooks/shared/useFormatters';
import { Toggle } from '@evalium/ui';
import { AssessmentContextInfo } from '@/Components/features/assessment';
import { type Assessment, type AssessmentAssignment, type AssessmentRouteContext, type Question, type QuestionResult } from '@/types';
import { type PaginationType } from '@/types/datatable';
import {
    ClockIcon,
    QuestionMarkCircleIcon,
    StarIcon,
    DocumentDuplicateIcon,
    UserGroupIcon,
} from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AssessmentHeader } from '@/Components/features/assessment/AssessmentHeader';
import { AssignmentList } from '@/Components/shared/lists';

interface AssignmentWithVirtual extends AssessmentAssignment {
    is_virtual?: boolean;
}

interface Props {
    assessment: Assessment;
    assignments: PaginationType<AssignmentWithVirtual>;
    routeContext?: AssessmentRouteContext;
}

const AssessmentShow: React.FC<Props> = ({ assessment, assignments, routeContext }) => {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const { formatDuration } = useFormatters();
    const [isToggling, setIsToggling] = useState(false);
    const [isDuplicating, setIsDuplicating] = useState(false);
    const [showDuplicateModal, setShowDuplicateModal] = useState(false);

    const isTeacher = routeContext?.role === 'teacher';
    const canToggle = isTeacher && routeContext?.publishRoute && routeContext?.unpublishRoute;
    const canDuplicate = isTeacher && routeContext?.duplicateRoute;
    const canEdit = isTeacher && routeContext?.editRoute;

    const totalPoints = useMemo(
        () => (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0),
        [assessment.questions],
    );

    const totalStudents = assignments.total;
    const questionsCount = (assessment.questions ?? []).length;

    const handleToggleStatus = () => {
        if (isToggling || !canToggle) return;
        setIsToggling(true);
        const toggleRoute = assessment.is_published
            ? routeContext!.unpublishRoute!
            : routeContext!.publishRoute!;
        router.post(
            route(toggleRoute, assessment.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsToggling(false),
            },
        );
    };

    const handleDuplicate = () => {
        if (isDuplicating || !canDuplicate) return;
        setIsDuplicating(true);
        router.post(
            route(routeContext!.duplicateRoute!, assessment.id),
            {},
            {
                onFinish: () => {
                    setIsDuplicating(false);
                    setShowDuplicateModal(false);
                },
            },
        );
    };

    const handleEditNavigation = () => {
        if (!canEdit) return;
        router.visit(route(routeContext!.editRoute!, assessment.id));
    };

    const getQuestionResult = useCallback(
        (question: Question): QuestionResult => ({
            isCorrect: null,
            userChoices: [],
            hasMultipleAnswers: question.type === 'multiple',
            feedback: null,
            score: undefined,
            userText:
                question.type === 'text'
                    ? t('assessment_pages.common.free_text_info')
                    : undefined,
        }),
        [t],
    );

    const handleGradeStudent = (assignment: AssignmentWithVirtual) => {
        if (!assignment.id || !routeContext) return;
        router.visit(
            route(routeContext.gradeRoute, {
                assessment: assessment.id,
                assignment: assignment.id,
            }),
        );
    };

    const handleViewResult = (assignment: AssignmentWithVirtual) => {
        if (!assignment.id || !routeContext) return;
        router.visit(
            route(routeContext.reviewRoute, {
                assessment: assessment.id,
                assignment: assignment.id,
            }),
        );
    };

    const pageBreadcrumbs = routeContext
        ? breadcrumbs.assessment.show(routeContext, assessment)
        : breadcrumbs.showTeacherAssessment(assessment);

    return (
        <AuthenticatedLayout title={assessment.title} breadcrumb={pageBreadcrumbs}>
            <div className="max-w-6xl mx-auto space-y-6">
                <Section
                    title={t('assessment_pages.common.subtitle')}
                    actions={
                        canToggle || canDuplicate || canEdit ? (
                            <div className="flex flex-col md:flex-row space-y-2 md:space-x-3 md:space-y-0">
                                {canToggle && (
                                    <Toggle
                                        checked={assessment.is_published}
                                        onChange={handleToggleStatus}
                                        disabled={isToggling}
                                        color="green"
                                        size="sm"
                                        showLabel
                                        activeLabel={t('assessment_pages.common.toggle_published')}
                                        inactiveLabel={t(
                                            'assessment_pages.common.toggle_unpublished',
                                        )}
                                    />
                                )}
                                {canDuplicate && (
                                    <Button
                                        onClick={() => setShowDuplicateModal(true)}
                                        color="secondary"
                                        variant="outline"
                                        size="sm"
                                        disabled={isDuplicating}
                                    >
                                        <DocumentDuplicateIcon className="h-4 w-4 mr-1" />
                                        {t('assessment_pages.common.duplicate')}
                                    </Button>
                                )}
                                {canEdit && (
                                    <Button
                                        onClick={handleEditNavigation}
                                        color="secondary"
                                        variant="outline"
                                        size="sm"
                                    >
                                        {t('assessment_pages.common.edit')}
                                    </Button>
                                )}
                            </div>
                        ) : undefined
                    }
                >
                    <AssessmentHeader
                        assessment={assessment}
                        showDescription={true}
                        showMetadata={false}
                    />
                    <div className="border-b border-b-gray-300 pb-4">
                        <AssessmentContextInfo
                            assessment={assessment}
                            role={routeContext?.role ?? 'teacher'}
                            showLevel
                        />
                    </div>

                    <Stat.Group columns={4} className="mt-6">
                        <Stat.Item
                            title={t('assessment_pages.common.questions')}
                            value={questionsCount}
                            icon={QuestionMarkCircleIcon}
                        />
                        <Stat.Item
                            title={t('assessment_pages.common.total_points')}
                            value={totalPoints}
                            icon={StarIcon}
                        />
                        <Stat.Item
                            title={t('assessment_pages.common.duration')}
                            value={formatDuration(assessment.duration_minutes ?? 0)}
                            icon={ClockIcon}
                        />
                        <Stat.Item
                            title={t('assessment_pages.common.concerned_students')}
                            value={totalStudents}
                            icon={UserGroupIcon}
                        />
                    </Stat.Group>
                </Section>

                <Section
                    title={t('assessment_pages.show.students_section_title')}
                    subtitle={t('assessment_pages.show.students_section_subtitle', {
                        count: totalStudents,
                    })}
                    collapsible
                    defaultOpen={false}
                >
                    <AssignmentList
                        data={assignments}
                        assessment={assessment}
                        totalPoints={totalPoints}
                        routeContext={routeContext}
                        onGrade={routeContext ? handleGradeStudent : undefined}
                        onViewResult={routeContext ? handleViewResult : undefined}
                    />
                </Section>

                <Section
                    title={t('assessment_pages.common.assessment_questions')}
                    collapsible
                    defaultOpen={false}
                >
                    {(assessment.questions ?? []).length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            <p>{t('assessment_pages.common.no_questions')}</p>
                            {canEdit && (
                                <div className="mt-4">
                                    <Button onClick={handleEditNavigation}>
                                        {t('assessment_pages.common.add_questions')}
                                    </Button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <QuestionList
                            questions={assessment.questions ?? []}
                            getQuestionResult={getQuestionResult}
                            isTeacherView={true}
                            showCorrectAnswers={true}
                            previewMode={true}
                        />
                    )}
                </Section>
            </div>

            {canDuplicate && (
                <ConfirmationModal
                    isOpen={showDuplicateModal}
                    onClose={() => setShowDuplicateModal(false)}
                    onConfirm={handleDuplicate}
                    title={t('assessment_pages.common.duplicate_modal_title')}
                    message={t('assessment_pages.common.duplicate_modal_message', {
                        title: assessment.title,
                    })}
                    confirmText={t('assessment_pages.common.duplicate_confirm')}
                    cancelText={t('assessment_pages.create.cancel')}
                    type="info"
                    loading={isDuplicating}
                />
            )}
        </AuthenticatedLayout>
    );
};

export default AssessmentShow;
