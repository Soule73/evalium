import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PageProps, Exam, ExamAssignment, User } from '@/types';
import { formatDate, formatDuration, formatDeadlineWarning } from '@/utils';
import { AlertEntry, Button, ExamHeader, Modal, Section, StatCard, TextEntry } from '@/Components';
import { breadcrumbs } from '@/utils';
import { route } from 'ziggy-js';
import { ClockIcon, DocumentTextIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import { useState, useMemo } from 'react';
import { trans } from '@/utils';

interface StudentExamShowProps extends PageProps {
    exam: Exam;
    assignment?: ExamAssignment;
    canTake: boolean;
    questionsCount?: number;
    creator: User;
    group?: {
        id: number;
        level: {
            id: number;
            name: string;
        };
    };
}

export default function StudentExamShow({ exam, assignment, canTake, questionsCount, creator, group }: StudentExamShowProps) {

    const translations = useMemo(() => ({
        importantTitle: trans('student_pages.show.important_title'),
        alertStableConnection: trans('student_pages.show.alert_stable_connection'),
        alertFullscreen: trans('student_pages.show.alert_fullscreen'),
        alertCheating: trans('student_pages.show.alert_cheating'),
        alertAutoSave: trans('student_pages.show.alert_auto_save'),
        alertTimeLimit: trans('student_pages.show.alert_time_limit'),
        startModalTitle: trans('student_pages.show.start_modal_title'),
        startModalQuestion: trans('student_pages.show.start_modal_question'),
        startModalConfirm: trans('student_pages.show.start_modal_confirm'),
        title: trans('student_pages.show.title'),
        backToExams: trans('student_pages.show.back_to_exams'),
        continueExam: trans('student_pages.show.continue_exam'),
        startExam: trans('student_pages.show.start_exam'),
        teacherCreator: trans('student_pages.show.teacher_creator'),
        duration: trans('student_pages.show.duration'),
        questions: trans('student_pages.show.questions'),
        status: trans('student_pages.show.status'),
        statusCompleted: trans('student_pages.show.status_completed'),
        statusInProgress: trans('student_pages.show.status_in_progress'),
        statusNotStarted: trans('student_pages.show.status_not_started'),
        importantDates: trans('student_pages.show.important_dates'),
        startDate: trans('student_pages.show.start_date'),
        endDate: trans('student_pages.show.end_date'),
    }), []);

    const deadlineWarning = useMemo(
        () => exam.end_time ? formatDeadlineWarning(exam.end_time) : null,
        [exam.end_time]
    );

    const [isModalOpen, setIsModalOpen] = useState(false);

    const statusValue = useMemo(() => {
        if (assignment?.submitted_at) return translations.statusCompleted;
        if (assignment?.started_at) return translations.statusInProgress;
        return translations.statusNotStarted;
    }, [assignment?.submitted_at, assignment?.started_at, translations]);

    const AlertMessage = (
        <AlertEntry type="warning" title={translations.importantTitle} >
            <ul className="list-disc list-inside space-y-1 text-sm">
                <li>{translations.alertStableConnection}</li>
                <li>{translations.alertFullscreen}</li>
                <li>{translations.alertCheating}</li>
                <li>{translations.alertAutoSave}</li>
                <li>{translations.alertTimeLimit}</li>
            </ul>
        </AlertEntry>
    );

    return (
        <AuthenticatedLayout
            title={exam.title}
            breadcrumb={group ? breadcrumbs.studentExamShow(group.level.name, group.id, exam.title) : breadcrumbs.studentExams()}
        >
            <Modal size='xl' isOpen={isModalOpen} onClose={() => setIsModalOpen(false)}>
                <div className=' flex flex-col justify-between'>
                    <div className='mx-auto my-4 flex flex-col items-center'>

                        <QuestionMarkCircleIcon className="w-12 h-12 mb-3 text-yellow-500 mx-auto" />
                        <h2 className="text-lg font-semibold mb-2">{translations.startModalTitle}</h2>
                        <p>{translations.startModalQuestion}</p>
                    </div>
                    {AlertMessage}
                    <div className="mt-4 flex justify-end space-x-2">
                        <Button size='sm' variant='outline' color="primary" onClick={() => {
                            setIsModalOpen(false);
                            router.visit(route('student.exams.take', exam.id));
                        }}>
                            {translations.startModalConfirm}
                        </Button>
                    </div>
                </div>
            </Modal>

            <Section title={translations.title}
                actions={
                    <div className="flex items-center space-x-4">
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            className=' w-max'
                            onClick={() => router.visit(route('student.exams.index'))}
                        >
                            {translations.backToExams}
                        </Button>

                        {canTake && (
                            <Button
                                color="primary"
                                size="sm"
                                onClick={() => setIsModalOpen(true)}
                            >
                                {assignment?.started_at ? translations.continueExam : translations.startExam}
                            </Button>
                        )
                        }
                    </div >
                }
            >
                <div className="flex items-start justify-between mb-6">
                    <div className=' space-y-3 '>
                        <ExamHeader exam={exam} showDescription={true} showMetadata={false} />

                        <TextEntry label={translations.teacherCreator} value={creator?.name} />
                    </div>
                    {deadlineWarning && (
                        <div className={`px-4 py-2 rounded-lg ${deadlineWarning.urgency === 'high'
                            ? 'bg-red-100 border border-red-200'
                            : deadlineWarning.urgency === 'medium'
                                ? 'bg-yellow-100 border border-yellow-200'
                                : 'bg-green-100 border border-green-200'
                            }`}>
                            <p className={`text-sm font-medium ${deadlineWarning.urgency === 'high'
                                ? 'text-red-800'
                                : deadlineWarning.urgency === 'medium'
                                    ? 'text-yellow-800'
                                    : 'text-green-800'
                                }`}>
                                {deadlineWarning.text}
                            </p>
                        </div>
                    )}
                </div>

                <div className="grid gap-y-2 grid-cols-1 lg:grid-cols-3 mb-8">
                    <StatCard
                        title={translations.duration}
                        value={formatDuration(exam.duration)}
                        icon={ClockIcon}
                        color="blue"
                        className=' lg:rounded-r-none! '
                    />
                    <StatCard
                        title={translations.questions}
                        value={questionsCount || 0}
                        icon={DocumentTextIcon}
                        color="green"
                        className=' lg:rounded-none! lg:border-x-0! '
                    />

                    <StatCard
                        title={translations.status}
                        value={statusValue}
                        icon={QuestionMarkCircleIcon}
                        color="purple"
                        className=' lg:rounded-l-none!  '
                    />
                </div>

                {
                    (exam.start_time || exam.end_time) && (
                        <div className="mb-8">
                            <h2 className="text-lg font-semibold text-gray-900 mb-3">{translations.importantDates}</h2>
                            <div className="bg-gray-50 rounded-lg p-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {exam.start_time && (
                                        <TextEntry label={translations.startDate} value={formatDate(exam.start_time)} />

                                    )}
                                    {exam.end_time && (
                                        <TextEntry label={translations.endDate} value={formatDate(exam.end_time)} />

                                    )}
                                </div>
                            </div>
                        </div>
                    )
                }
                {AlertMessage}

            </Section >
        </AuthenticatedLayout >
    );
}
