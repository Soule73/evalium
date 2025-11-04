import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Exam, ExamAssignment, User } from '@/types';
import { formatDate, formatDuration, formatDeadlineWarning } from '@/utils/formatters';
import { Button } from '@/Components';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { route } from 'ziggy-js';
import { ClockIcon, DocumentTextIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import AlertEntry from '@/Components/AlertEntry';
import Section from '@/Components/Section';
import { useState } from 'react';
import TextEntry from '@/Components/TextEntry';
import Modal from '@/Components/Modal';
import StatCard from '@/Components/StatCard';
import { ExamHeader } from '@/Components/exam';
import { trans } from '@/utils/translations';

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

    const deadlineWarning = exam.end_time
        ? formatDeadlineWarning(
            exam.end_time
        )
        : null;

    const [isModalOpen, setIsModalOpen] = useState(false);

    const AlertMessage = (
        <AlertEntry type="warning" title={trans('student_pages.show.important_title')} >
            <ul className="list-disc list-inside space-y-1 text-sm">
                <li>{trans('student_pages.show.alert_stable_connection')}</li>
                <li>{trans('student_pages.show.alert_fullscreen')}</li>
                <li>{trans('student_pages.show.alert_cheating')}</li>
                <li>{trans('student_pages.show.alert_auto_save')}</li>
                <li>{trans('student_pages.show.alert_time_limit')}</li>
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
                        <h2 className="text-lg font-semibold mb-2">{trans('student_pages.show.start_modal_title')}</h2>
                        <p>{trans('student_pages.show.start_modal_question')}</p>
                    </div>
                    {AlertMessage}
                    <div className="mt-4 flex justify-end space-x-2">
                        <Button size='sm' variant='outline' color="primary" onClick={() => {
                            setIsModalOpen(false);
                            router.visit(route('student.exams.take', exam.id));
                        }}>
                            {trans('student_pages.show.start_modal_confirm')}
                        </Button>
                    </div>
                </div>
            </Modal>

            <Section title={trans('student_pages.show.title')}
                actions={
                    <div className="flex items-center space-x-4">
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            className=' w-max'
                            onClick={() => router.visit(route('student.exams.index'))}
                        >
                            {trans('student_pages.show.back_to_exams')}
                        </Button>

                        {canTake && (
                            <Button
                                color="primary"
                                size="sm"
                                onClick={() => setIsModalOpen(true)}
                            >
                                {
                                    assignment?.started_at
                                        ? trans('student_pages.show.continue_exam')
                                        : trans('student_pages.show.start_exam')
                                }
                            </Button>
                        )
                        }
                    </div >
                }
            >
                <div className="flex items-start justify-between mb-6">
                    <div className=' space-y-3 '>
                        <ExamHeader exam={exam} showDescription={true} showMetadata={false} />

                        <TextEntry label={trans('student_pages.show.teacher_creator')} value={creator?.name} />
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
                        title={trans('student_pages.show.duration')}
                        value={formatDuration(exam.duration)}
                        icon={ClockIcon}
                        color="blue"
                        className=' lg:rounded-r-none! '
                    />
                    <StatCard
                        title={trans('student_pages.show.questions')}
                        value={questionsCount || 0}
                        icon={DocumentTextIcon}
                        color="green"
                        className=' lg:rounded-none! lg:border-x-0! '
                    />

                    <StatCard
                        title={trans('student_pages.show.status')}
                        value={
                            assignment?.submitted_at
                                ? trans('student_pages.show.status_completed') :
                                assignment?.started_at ? trans('student_pages.show.status_in_progress') :
                                    trans('student_pages.show.status_not_started')}
                        icon={QuestionMarkCircleIcon}
                        color="purple"
                        className=' lg:rounded-l-none!  '
                    />
                </div>

                {
                    (exam.start_time || exam.end_time) && (
                        <div className="mb-8">
                            <h2 className="text-lg font-semibold text-gray-900 mb-3">{trans('student_pages.show.important_dates')}</h2>
                            <div className="bg-gray-50 rounded-lg p-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {exam.start_time && (
                                        <TextEntry label={trans('student_pages.show.start_date')} value={formatDate(exam.start_time)} />

                                    )}
                                    {exam.end_time && (
                                        <TextEntry label={trans('student_pages.show.end_date')} value={formatDate(exam.end_time)} />

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
