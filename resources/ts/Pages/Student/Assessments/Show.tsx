import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type Assessment,
    type AssessmentAssignment,
    type AvailabilityStatus,
    type PageProps,
} from '@/types';
import { AlertEntry, Button, Modal, Section, Stat, TextEntry } from '@/Components';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatDate } from '@/utils';
import { formatAssignmentStatus } from '@/utils/assessment';
import { useAssessmentCountdown } from '@/hooks/features/assessment';
import {
    ClockIcon,
    DocumentTextIcon,
    QuestionMarkCircleIcon,
    EyeIcon,
} from '@heroicons/react/24/outline';

interface StudentAssessmentShowProps extends PageProps {
    assessment: Assessment;
    assignment: AssessmentAssignment;
    availability: AvailabilityStatus;
    canViewResults: boolean;
}

export default function Show({
    assessment,
    assignment,
    availability,
    canViewResults,
}: StudentAssessmentShowProps) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const isHomework = assessment.delivery_mode === 'homework';
    const hasStarted = !!assignment.started_at;

    const { countdown, isStarting } = useAssessmentCountdown(
        assessment.scheduled_at ?? null,
        availability.reason === 'assessment_not_started' && !isHomework,
    );

    const translations = useMemo(
        () => ({
            title: t('student_assessment_pages.show.title'),
            backToAssessments: t('student_assessment_pages.show.back_to_assessments'),
            subject: t('student_assessment_pages.show.subject'),
            class: t('student_assessment_pages.show.class'),
            teacher: t('student_assessment_pages.show.teacher'),
            duration: t('student_assessment_pages.show.duration'),
            minutes: t('student_assessment_pages.show.minutes'),
            questions: t('student_assessment_pages.show.questions'),
            status: t('student_assessment_pages.show.status'),
            importantDates: t('student_assessment_pages.show.important_dates'),
            scheduledDate: t('student_assessment_pages.show.scheduled_date'),
            dueDate: t('student_assessment_pages.show.due_date'),
            submittedDate: t('student_assessment_pages.show.submitted_date'),
            importantTitle: t('student_assessment_pages.show.important_title'),
            alertStableConnection: t('student_assessment_pages.show.alert_stable_connection'),
            alertFullscreen: t('student_assessment_pages.show.alert_fullscreen'),
            alertCheating: t('student_assessment_pages.show.alert_cheating'),
            alertAutoSave: t('student_assessment_pages.show.alert_auto_save'),
            alertTimeLimit: t('student_assessment_pages.show.alert_time_limit'),
            alertHomeworkMultiSession: t(
                'student_assessment_pages.show.alert_homework_multi_session',
            ),
            alertHomeworkDueDate: t('student_assessment_pages.show.alert_homework_due_date'),
            description: t('student_assessment_pages.show.description'),
            noDescription: t('student_assessment_pages.show.no_description'),
            viewResults: t('student_assessment_pages.show.view_result'),
            resultsNotYetAvailable: t('student_assessment_pages.show.results_not_yet_available'),
            resultsAvailableAfter: t('student_assessment_pages.show.results_available_after'),
            resultsWaitingGrading: t('student_assessment_pages.show.results_waiting_grading'),
            startedDate: t('student_assessment_pages.show.started_date'),
            assessmentUnavailable: t('student_assessment_pages.show.assessment_unavailable'),
            countdownTitle: t('student_assessment_pages.show.countdown_title'),
            countdownDays: t('student_assessment_pages.show.countdown_days'),
            countdownHours: t('student_assessment_pages.show.countdown_hours'),
            countdownMinutes: t('student_assessment_pages.show.countdown_minutes'),
            countdownSeconds: t('student_assessment_pages.show.countdown_seconds'),
            countdownStarting: t('student_assessment_pages.show.countdown_starting'),
        }),
        [t],
    );

    const statsTranslations = useMemo(
        () => ({
            startAssessment: isHomework
                ? t('student_assessment_pages.show.start_working')
                : t('student_assessment_pages.show.start_assessment'),
            continueAssessment: isHomework
                ? t('student_assessment_pages.show.continue_working')
                : t('student_assessment_pages.show.continue_assessment'),
            startModalTitle: isHomework
                ? t('student_assessment_pages.show.start_modal_title_homework')
                : t('student_assessment_pages.show.start_modal_title'),
            startModalQuestion: isHomework
                ? t('student_assessment_pages.show.start_modal_question_homework')
                : t('student_assessment_pages.show.start_modal_question'),
            startModalConfirm: isHomework
                ? t('student_assessment_pages.show.start_modal_confirm_homework')
                : t('student_assessment_pages.show.start_modal_confirm'),
        }),
        [t, isHomework],
    );

    const statusValue = useMemo(
        () => formatAssignmentStatus(t, assignment.status).label,
        [assignment.status, t],
    );

    const isSubmitted = !!assignment.submitted_at;
    const canTake = !isSubmitted && availability.available;

    const unavailabilityReasonMap: Record<string, string> = useMemo(
        () => ({
            assessment_not_published: t('messages.assessment_not_published'),
            assessment_due_date_passed: t('messages.assessment_due_date_passed'),
            assessment_not_started: t('messages.assessment_not_started'),
            assessment_ended: t('messages.assessment_ended'),
        }),
        [t],
    );

    const unavailabilityMessage =
        !isSubmitted && !availability.available && availability.reason
            ? unavailabilityReasonMap[availability.reason] || translations.assessmentUnavailable
            : null;

    const alertMessage = isSubmitted ? null : isHomework ? (
        <AlertEntry type="info" title={translations.importantTitle}>
            <ul className="list-disc list-inside space-y-1 text-sm">
                <li>{translations.alertStableConnection}</li>
                <li>{translations.alertHomeworkMultiSession}</li>
                <li>{translations.alertAutoSave}</li>
                {assessment.due_date && <li>{translations.alertHomeworkDueDate}</li>}
            </ul>
        </AlertEntry>
    ) : (
        <AlertEntry type="warning" title={translations.importantTitle}>
            <ul className="list-disc list-inside space-y-1 text-sm">
                <li>{translations.alertStableConnection}</li>
                <li>{translations.alertFullscreen}</li>
                <li>{translations.alertCheating}</li>
                <li>{translations.alertAutoSave}</li>
                <li>{translations.alertTimeLimit}</li>
            </ul>
        </AlertEntry>
    );

    const ctaLabel = canTake
        ? hasStarted
            ? statsTranslations.continueAssessment
            : statsTranslations.startAssessment
        : null;

    const showViewResults = canViewResults;

    const showResultsPending = isSubmitted && !canViewResults;
    const resultsAvailableAt = assessment.results_available_at;

    const handleStartAssessment = () => {
        setIsModalOpen(false);
        router.post(
            route('student.assessments.start', assessment.id),
            {},
            {
                onSuccess: () => {
                    router.visit(route('student.assessments.take', assessment.id));
                },
            },
        );
    };

    return (
        <AuthenticatedLayout
            title={assessment.title}
            breadcrumb={breadcrumbs.student.showAssessment(assessment)}
        >
            <Modal size="xl" isOpen={isModalOpen} onClose={() => setIsModalOpen(false)}>
                <div className="flex flex-col justify-between">
                    <div className="mx-auto my-4 flex flex-col items-center">
                        <QuestionMarkCircleIcon className="w-12 h-12 mb-3 text-yellow-500 mx-auto" />
                        <h2 className="text-lg font-semibold mb-2">
                            {statsTranslations.startModalTitle}
                        </h2>
                        <p>{statsTranslations.startModalQuestion}</p>
                    </div>
                    {alertMessage}
                    <div className="mt-4 flex justify-end space-x-2">
                        <Button
                            size="sm"
                            variant="outline"
                            color="secondary"
                            onClick={() => setIsModalOpen(false)}
                        >
                            {t('commons/ui.cancel')}
                        </Button>
                        <Button size="sm" color="primary" onClick={handleStartAssessment}>
                            {statsTranslations.startModalConfirm}
                        </Button>
                    </div>
                </div>
            </Modal>

            <Section
                title={translations.title}
                actions={
                    <div className="flex items-center space-x-4">
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('student.assessments.index'))}
                        >
                            {translations.backToAssessments}
                        </Button>

                        {canTake && ctaLabel && (
                            <Button color="primary" size="sm" onClick={() => setIsModalOpen(true)}>
                                {ctaLabel}
                            </Button>
                        )}

                        {showViewResults && (
                            <Button
                                color="primary"
                                size="sm"
                                onClick={() =>
                                    router.visit(route('student.assessments.result', assessment.id))
                                }
                            >
                                <EyeIcon className="w-4 h-4 mr-1" />
                                {translations.viewResults}
                            </Button>
                        )}
                    </div>
                }
            >
                <div className="space-y-6">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            {assessment.title}
                        </h1>
                        {assessment.description && (
                            <div className="mb-4">
                                <h3 className="text-sm font-medium text-gray-700 mb-1">
                                    {translations.description}
                                </h3>
                                <p className="text-gray-600">{assessment.description}</p>
                            </div>
                        )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <TextEntry
                            label={translations.subject}
                            value={assessment.class_subject?.subject?.name || '-'}
                        />
                        <TextEntry
                            label={translations.class}
                            value={
                                assessment.class_subject?.class?.display_name ??
                                assessment.class_subject?.class?.name ??
                                '-'
                            }
                        />
                        <TextEntry
                            label={translations.teacher}
                            value={assessment.class_subject?.teacher?.name || '-'}
                        />
                    </div>

                    <Stat.Group columns={3}>
                        {isHomework && assessment.due_date ? (
                            <Stat.Item
                                title={translations.dueDate}
                                value={formatDate(assessment.due_date, 'datetime')}
                                icon={ClockIcon}
                            />
                        ) : (
                            <Stat.Item
                                title={translations.duration}
                                value={`${assessment.duration_minutes} ${translations.minutes}`}
                                icon={ClockIcon}
                            />
                        )}
                        <Stat.Item
                            title={translations.questions}
                            value={assessment.questions?.length || 0}
                            icon={DocumentTextIcon}
                        />
                        <Stat.Item
                            title={translations.status}
                            value={statusValue}
                            icon={QuestionMarkCircleIcon}
                        />
                    </Stat.Group>

                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 mb-3">
                            {translations.importantDates}
                        </h2>
                        <div className="bg-gray-50 rounded-lg p-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <TextEntry
                                    label={
                                        isHomework
                                            ? translations.dueDate
                                            : translations.scheduledDate
                                    }
                                    value={formatDate(
                                        (isHomework
                                            ? assessment.due_date
                                            : assessment.scheduled_at) ?? '',
                                    )}
                                />
                                {assignment.started_at && (
                                    <TextEntry
                                        label={translations.startedDate}
                                        value={formatDate(assignment.started_at)}
                                    />
                                )}
                                {assignment.submitted_at && (
                                    <TextEntry
                                        label={translations.submittedDate}
                                        value={formatDate(assignment.submitted_at)}
                                    />
                                )}
                            </div>
                        </div>
                    </div>

                    {availability.reason === 'assessment_not_started' && !isSubmitted && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <div className="flex items-center gap-2 mb-3">
                                <ClockIcon className="h-5 w-5 text-amber-600 shrink-0" />
                                <p className="text-sm font-semibold text-amber-800">
                                    {translations.countdownTitle}
                                </p>
                            </div>
                            {isStarting ? (
                                <p className="text-center text-amber-700 animate-pulse font-medium">
                                    {translations.countdownStarting}
                                </p>
                            ) : (
                                countdown && (
                                    <div className="flex justify-center gap-6">
                                        {countdown.days > 0 && (
                                            <div className="text-center">
                                                <span className="text-3xl font-bold text-amber-700">
                                                    {String(countdown.days).padStart(2, '0')}
                                                </span>
                                                <p className="text-xs text-amber-600 mt-1">
                                                    {translations.countdownDays}
                                                </p>
                                            </div>
                                        )}
                                        <div className="text-center">
                                            <span className="text-3xl font-bold text-amber-700">
                                                {String(countdown.hours).padStart(2, '0')}
                                            </span>
                                            <p className="text-xs text-amber-600 mt-1">
                                                {translations.countdownHours}
                                            </p>
                                        </div>
                                        <div className="text-center">
                                            <span className="text-3xl font-bold text-amber-700">
                                                {String(countdown.minutes).padStart(2, '0')}
                                            </span>
                                            <p className="text-xs text-amber-600 mt-1">
                                                {translations.countdownMinutes}
                                            </p>
                                        </div>
                                        <div className="text-center">
                                            <span className="text-3xl font-bold text-amber-700">
                                                {String(countdown.seconds).padStart(2, '0')}
                                            </span>
                                            <p className="text-xs text-amber-600 mt-1">
                                                {translations.countdownSeconds}
                                            </p>
                                        </div>
                                    </div>
                                )
                            )}
                            <p className="text-xs text-amber-600 text-center mt-3">
                                {formatDate(assessment.scheduled_at ?? '', 'datetime')}
                            </p>
                        </div>
                    )}

                    {unavailabilityMessage && availability.reason !== 'assessment_not_started' && (
                        <AlertEntry type="error" title={translations.assessmentUnavailable}>
                            <p className="text-sm">{unavailabilityMessage}</p>
                        </AlertEntry>
                    )}

                    {showResultsPending && (
                        <AlertEntry type="info" title={translations.resultsNotYetAvailable}>
                            <p className="text-sm">
                                {resultsAvailableAt
                                    ? `${translations.resultsAvailableAfter} ${formatDate(resultsAvailableAt, 'datetime')}`
                                    : translations.resultsWaitingGrading}
                            </p>
                        </AlertEntry>
                    )}

                    {alertMessage}
                </div>
            </Section>
        </AuthenticatedLayout>
    );
}
