import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Assessment, AssessmentAssignment, PageProps } from '@/types';
import {
  AlertEntry,
  Button,
  Modal,
  Section,
  StatCard,
  TextEntry,
} from '@/Components';
import { trans, formatDate } from '@/utils';
import { ClockIcon, DocumentTextIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';

interface StudentAssessmentShowProps extends PageProps {
  assessment: Assessment;
  assignment: AssessmentAssignment;
}

export default function Show({ assessment, assignment }: StudentAssessmentShowProps) {
  const [isModalOpen, setIsModalOpen] = useState(false);

  const translations = {
    title: trans('student_assessment_pages.show.title'),
    backToAssessments: trans('student_assessment_pages.show.back_to_assessments'),
    startAssessment: trans('student_assessment_pages.show.start_assessment'),
    continueAssessment: trans('student_assessment_pages.show.continue_assessment'),
    startModalTitle: trans('student_assessment_pages.show.start_modal_title'),
    startModalQuestion: trans('student_assessment_pages.show.start_modal_question'),
    startModalConfirm: trans('student_assessment_pages.show.start_modal_confirm'),
    subject: trans('student_assessment_pages.show.subject'),
    class: trans('student_assessment_pages.show.class'),
    teacher: trans('student_assessment_pages.show.teacher'),
    duration: trans('student_assessment_pages.show.duration'),
    minutes: trans('student_assessment_pages.show.minutes'),
    questions: trans('student_assessment_pages.show.questions'),
    status: trans('student_assessment_pages.show.status'),
    statusCompleted: trans('student_assessment_pages.show.status_completed'),
    statusInProgress: trans('student_assessment_pages.show.status_in_progress'),
    statusNotStarted: trans('student_assessment_pages.show.status_not_started'),
    importantDates: trans('student_assessment_pages.show.important_dates'),
    assignedDate: trans('student_assessment_pages.show.assigned_date'),
    dueDate: trans('student_assessment_pages.show.due_date'),
    startedDate: trans('student_assessment_pages.show.started_date'),
    submittedDate: trans('student_assessment_pages.show.submitted_date'),
    importantTitle: trans('student_assessment_pages.show.important_title'),
    alertStableConnection: trans('student_assessment_pages.show.alert_stable_connection'),
    alertFullscreen: trans('student_assessment_pages.show.alert_fullscreen'),
    alertCheating: trans('student_assessment_pages.show.alert_cheating'),
    alertAutoSave: trans('student_assessment_pages.show.alert_auto_save'),
    alertTimeLimit: trans('student_assessment_pages.show.alert_time_limit'),
    description: trans('student_assessment_pages.show.description'),
    noDescription: trans('student_assessment_pages.show.no_description'),
  };


  const statusValue = useMemo(() => {
    if (assignment.submitted_at) return translations.statusCompleted;
    if (assignment.started_at) return translations.statusInProgress;
    return translations.statusNotStarted;
  }, [assignment.submitted_at, assignment.started_at, translations]);

  const canTake = !assignment.submitted_at;

  const alertMessage = (
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

  const handleStartAssessment = () => {
    setIsModalOpen(false);
    router.post(
      route('student.assessments.start', assessment.id),
      {},
      {
        onSuccess: () => {
          router.visit(route('student.assessments.take', assessment.id));
        },
      }
    );
  };

  return (
    <AuthenticatedLayout title={assessment.title}>
      <Modal size="xl" isOpen={isModalOpen} onClose={() => setIsModalOpen(false)}>
        <div className="flex flex-col justify-between">
          <div className="mx-auto my-4 flex flex-col items-center">
            <QuestionMarkCircleIcon className="w-12 h-12 mb-3 text-yellow-500 mx-auto" />
            <h2 className="text-lg font-semibold mb-2">{translations.startModalTitle}</h2>
            <p>{translations.startModalQuestion}</p>
          </div>
          {alertMessage}
          <div className="mt-4 flex justify-end space-x-2">
            <Button
              size="sm"
              variant="outline"
              color="secondary"
              onClick={() => setIsModalOpen(false)}
            >
              {trans('components.confirmation_modal.cancel')}
            </Button>
            <Button size="sm" color="primary" onClick={handleStartAssessment}>
              {translations.startModalConfirm}
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

            {canTake && (
              <Button color="primary" size="sm" onClick={() => setIsModalOpen(true)}>
                {assignment.started_at ? translations.continueAssessment : translations.startAssessment}
              </Button>
            )}
          </div>
        }
      >
        <div className="space-y-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">{assessment.title}</h1>
            {assessment.description && (
              <div className="mb-4">
                <h3 className="text-sm font-medium text-gray-700 mb-1">{translations.description}</h3>
                <p className="text-gray-600">{assessment.description}</p>
              </div>
            )}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <TextEntry
              label={translations.subject}
              value={assessment.class_subject?.subject?.name || '-'}
            />
            <TextEntry label={translations.class} value={assessment.class_subject?.class?.name || '-'} />
            <TextEntry label={translations.teacher} value={assessment.class_subject?.teacher?.name || '-'} />
          </div>

          <div className="grid gap-y-2 grid-cols-1 lg:grid-cols-3">
            <StatCard
              title={translations.duration}
              value={`${assessment.duration_minutes} ${translations.minutes}`}
              icon={ClockIcon}
              color="blue"
              className="lg:rounded-r-none"
            />
            <StatCard
              title={translations.questions}
              value={assessment.questions?.length || 0}
              icon={DocumentTextIcon}
              color="green"
              className="lg:rounded-none lg:border-x-0"
            />
            <StatCard
              title={translations.status}
              value={statusValue}
              icon={QuestionMarkCircleIcon}
              color="purple"
              className="lg:rounded-l-none"
            />
          </div>

          <div>
            <h2 className="text-lg font-semibold text-gray-900 mb-3">{translations.importantDates}</h2>
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <TextEntry
                  label={translations.assignedDate}
                  value={formatDate(assignment.assigned_at)}
                />
                <TextEntry label={translations.dueDate} value={formatDate(assessment.scheduled_at)} />
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

          {alertMessage}
        </div>
      </Section>
    </AuthenticatedLayout>
  );
}
